<?php

namespace App\Features\Employees\ImportEmployees;

use App\Features\Employees\Enums\GovernmentId;
use App\Features\Employees\Forms\EmployeeRequest;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use SplFileObject;

/**
 * Bulk employee import. Every row is validated with the same rules as the
 * employee form; the import is all-or-nothing.
 */
class ImportEmployeesAction
{
    public const COLUMNS = [
        'employee_no', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'gender', 'civil_status',
        'email', 'mobile', 'address', 'department_code', 'position_title', 'employment_type', 'status', 'hired_at',
        'rate_type', 'basic_rate', 'is_minimum_wage_earner', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin',
        'bank_name', 'bank_account_name', 'bank_account_no',
    ];

    private const REQUIRED_COLUMNS = ['employee_no', 'first_name', 'last_name', 'employment_type', 'hired_at', 'rate_type', 'basic_rate'];

    private const UNIQUE_COLUMNS = ['employee_no', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin'];

    /**
     * @return array{imported: int, errors: list<string>}
     */
    public function handle(string $path): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $header = null;
        $rows = [];
        $errors = [];
        $seen = [];
        $departments = Department::query()->pluck('id', 'code')->mapWithKeys(fn ($id, $code) => [strtoupper($code) => $id]);

        foreach ($file as $index => $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $row = array_map(fn ($v) => trim((string) $v), $row);

            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim($h, "\u{FEFF} ")), $row);
                $missing = array_diff(self::REQUIRED_COLUMNS, $header);

                if ($missing !== []) {
                    return ['imported' => 0, 'errors' => ['Missing columns: '.implode(', ', $missing).'.']];
                }

                continue;
            }

            $line = $index + 1;
            $data = array_filter(
                array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), '')),
                fn ($v, $k) => in_array($k, self::COLUMNS, true) && $v !== '',
                ARRAY_FILTER_USE_BOTH,
            );
            $data += ['status' => 'active', 'is_minimum_wage_earner' => '0'];
            $data['is_minimum_wage_earner'] = in_array(strtolower($data['is_minimum_wage_earner']), ['1', 'y', 'yes', 'true'], true) ? '1' : '0';
            $data = array_merge($data, EmployeeRequest::normalize($data));

            $rowErrors = [];

            if (isset($data['department_code'])) {
                $data['department_id'] = $departments[strtoupper($data['department_code'])] ?? null;
                $data['department_id'] ?? $rowErrors[] = "unknown department code [{$data['department_code']}]";
            }

            if (isset($data['position_title'])) {
                $data['position_id'] = Position::query()
                    ->where('title', $data['position_title'])
                    ->when($data['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
                    ->value('id');
                $data['position_id'] ?? $rowErrors[] = "unknown position [{$data['position_title']}]";
            }

            foreach (self::UNIQUE_COLUMNS as $column) {
                if (! empty($data[$column])) {
                    if (isset($seen[$column][$data[$column]])) {
                        $rowErrors[] = "duplicate {$column} in the file (also on line {$seen[$column][$data[$column]]})";
                    }
                    $seen[$column][$data[$column]] = $line;
                }
            }

            $validator = Validator::make($data, EmployeeRequest::rulesFor(null, $data['status'] ?? null), [], EmployeeRequest::attributeNames());

            foreach ($validator->errors()->all() as $message) {
                $rowErrors[] = rtrim($message, '.');
            }

            if ($rowErrors !== []) {
                $errors[] = "Line {$line}: ".implode('; ', $rowErrors).'.';

                continue;
            }

            $rows[] = array_intersect_key($validator->validated(), array_flip((new Employee)->getFillable()));
        }

        if ($header === null) {
            return ['imported' => 0, 'errors' => ['The file is empty.']];
        }

        if ($errors !== []) {
            return ['imported' => 0, 'errors' => array_slice($errors, 0, 100)];
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                Employee::query()->create($row);
            }
        });

        return ['imported' => count($rows), 'errors' => []];
    }

    /**
     * @return list<string>
     */
    public static function sampleRow(): array
    {
        return [
            'EMP-10001', 'Juan', 'Santos', 'Dela Cruz', '', '1990-05-14', 'male', 'single', 'juan@example.com', '09171234567',
            'Quezon City', 'OPS', 'Production Staff', 'regular', 'active', '2024-01-08', 'daily', '645', 'yes',
            GovernmentId::Sss->format('3412345678'), GovernmentId::PhilHealth->format('123456789012'),
            GovernmentId::PagIbig->format('121212121212'), GovernmentId::Tin->format('123456789'), 'BDO', 'Juan S. Dela Cruz', '001234567890',
        ];
    }
}
