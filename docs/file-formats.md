# Government and bank file formats

All files are generated from **finalized** payroll only. Configure the employer details used in headers with
`EMPLOYER_NAME`, `EMPLOYER_TIN`, `EMPLOYER_TIN_BRANCH`, `EMPLOYER_RDO`, `EMPLOYER_SSS_NO`, `EMPLOYER_PHILHEALTH_NO`,
`EMPLOYER_PAGIBIG_NO`, `EMPLOYER_BANK_COMPANY_CODE` and `EMPLOYER_BANK_ACCOUNT_NO`.

> Agencies and banks revise their upload specifications. These layouts follow the published structure of each form
> but must be **validated with the agency's or bank's own validator / test upload** before the first live submission.
> Each format is a small class (`app/Features/Payroll/EFiling/Formats`, `app/Features/Payroll/ExportBankFile/Formats`)
> so a changed layout is a local edit.

## Government (Payroll → Government reports)

| Format | File | Layout |
|--------|------|--------|
| SSS R3 | `R3-YYYYMM.txt` | Fixed width. `00` + employer SSS no (10) + employer name (40) + month `YYYYMM`; `20` + SSS no (10) + last name (20) + first name (20) + MI (1) + SS EE+ER (9, centavos) + EC (7, centavos); `99` + count (6) + total SS (12) + total EC (10) |
| PhilHealth RF-1 | `RF1-YYYYMM.csv` | `PHILHEALTH_NO, SURNAME, GIVEN_NAME, MIDDLE_NAME, DATE_OF_BIRTH, SEX, MONTHLY_SALARY, PERSONAL_SHARE, EMPLOYER_SHARE, EMPLOYEE_STATUS, APPLICABLE_PERIOD` |
| Pag-IBIG MCRF | `MCRF-YYYYMM.csv` | `PAGIBIG_MID, LAST_NAME, FIRST_NAME, MIDDLE_NAME, BIRTHDATE, PERIOD_COVERED, MONTHLY_COMPENSATION, EE_SHARE, ER_SHARE, TIN, MEMBERSHIP_PROGRAM` |
| BIR 1604-C alphalist | `<TIN>0000<1231YYYY>1604C.DAT` | `H1604C` header; `D1` (schedule 1, non-MWE) / `D2` (schedule 2, MWE) detail lines with quoted names and amounts (gross, exempt 13th month, de minimis, MWE-exempt, contributions, total non-taxable, taxable, tax due, tax withheld, adjustment); `C1` control totals |

Names are upper-case ASCII ("José" → "JOSE").

## Banks (Payroll run → Bank file)

| Format | Layout |
|--------|--------|
| Generic CSV | Account number, account name, amount, employee no., bank, reference |
| Generic fixed width | `H` company/pay date/count/total, `D` account (20) + amount (15, centavos) + name (40) + employee no (15), `T` count/total |
| BDO | CSV without header: account no (12, zero-padded), amount, account name |
| BPI | Fixed width: `H` company code (5) + funding account (10) + credit date `MMDDYY` + count (5) + total (15); `D` account (10) + amount (12, centavos) + name (40) |
| Metrobank | CSV: ACCOUNT NUMBER, EMPLOYEE NAME, AMOUNT, REMARKS |
| Security Bank | CSV: ACCOUNT_NO, ACCOUNT_NAME, AMOUNT, REMARKS + `TOTAL` control row |
