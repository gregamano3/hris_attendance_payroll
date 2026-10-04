<?php

namespace App\Features\Payroll\EFiling;

use App\Features\Payroll\EFiling\Formats\BirAlphalist;
use App\Features\Payroll\EFiling\Formats\PagIbigMcrf;
use App\Features\Payroll\EFiling\Formats\PhilHealthRf1;
use App\Features\Payroll\EFiling\Formats\SssR3;
use App\Features\Payroll\Queries\AnnualCompensation;
use App\Features\Payroll\Queries\MonthlyContributions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agency upload files built from finalized payroll.
 */
class EFilingController
{
    public const FORMATS = [SssR3::class, PhilHealthRf1::class, PagIbigMcrf::class, BirAlphalist::class];

    public function __invoke(Request $request, string $format, MonthlyContributions $monthly, AnnualCompensation $annual): Response
    {
        $class = collect(self::FORMATS)->first(fn (string $c) => (new $c)->key() === $format) ?? abort(404);
        /** @var EFilingFormat $file */
        $file = new $class;

        if ($file->frequency() === 'annual') {
            $period = Carbon::create($request->integer('year') ?: today()->year)->startOfYear();
            $rows = $annual->forYear($period->year);
        } else {
            $period = rescue(fn () => Carbon::createFromFormat('Y-m', $request->string('month')->toString())->startOfMonth(), today()->subMonth()->startOfMonth(), false);
            $rows = $monthly->forMonth($period);
        }

        return response($file->render($rows, $period), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$file->filename($period).'"',
        ]);
    }

    /**
     * @return list<EFilingFormat>
     */
    public static function formats(): array
    {
        return array_map(fn (string $c) => new $c, self::FORMATS);
    }
}
