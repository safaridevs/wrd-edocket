<?php

namespace App\Utils;

use App\Libraries\ApplicationConstants;
use App\Models\DamSafety\Audit;
use App\Providers\RouteServiceProvider;
use App\Templates\LoanApp\PDFTemplateStateLetterhead;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mpdf\MpdfException;
use SodiumException;

/**
 * Class ApplicationUtils
 * @package App\Utils
 */
class ApplicationUtils
{

    /**
     * Get Application Name return null if invalid
     * @param bool $redirect - return the application's landing route for a logged-in user
     * @return string|null
     */
    public function getApplicationFromUrl(bool $redirect = false): ?string
    {
        $currentURL = url()->current();
        $returnString = null;

        foreach (ApplicationConstants::APPLICATIONS_LIST as $applicationName){
            if(str_contains($currentURL, strtolower($applicationName))) {
                switch ($applicationName) {
                    case ApplicationConstants::DAM_SAFETY:
                        $returnString = $redirect ? 'damsafety.inventory' : $applicationName;
                        break;
                    case ApplicationConstants::PLANT_LIST:
                        $returnString = $redirect ? 'plantlist.index' : $applicationName;
                        break;
                    case ApplicationConstants::WATER_MANAGEMENT:
                        $returnString = $redirect ? 'watermanagement.index' : $applicationName;
                        break;
                    case ApplicationConstants::LOAN_APP:
                        $returnString = $redirect ? 'borrowers' : $applicationName;
                        break;
                    case ApplicationConstants::NMWRRS:
                        $returnString = $redirect?'nmwrrs.index':$applicationName;
                        break;
                    case ApplicationConstants::RBTS:
                        $returnString = $redirect ? 'rbts.index' : $applicationName;
                        break;
                    case ApplicationConstants::E_DOCKET:
                        $returnString = $redirect ? 'edocket.index' : $applicationName;
                        break;
                    default:
                        Log::warning("invalid url");
                };
            }
        }
        return $returnString;
    }

    /**
     * Redirect the user to the home page for the request url
     * WaterManagement has two entrances, ppp and waterManagement
     * @return string
     */
    public function getHomePageFromUrl(): string
    {

        $application = $this->getApplicationFromUrl();
        if ($application == ApplicationConstants::WATER_MANAGEMENT && Gate::check('isPPPWaterManagement')) {
            $application = ApplicationConstants::WATER_MANAGEMENT_PPP;
        }
        if ($application == ApplicationConstants::NMWRRS && !Gate::check('isNMWRRSInternal')) {
            $application = ApplicationConstants::NMWRRS_EXTERNAL;
        }
        //Plant List has a different home page if user is logged in
        if($application == ApplicationConstants::PLANT_LIST) {
            if (Auth::check()) {
                return  RouteServiceProvider::PLANT_LIST_AUTHENTICATED_HOME;
            }
        }
        $path = match ($application) {
            ApplicationConstants::DAM_SAFETY => RouteServiceProvider::DAM_SAFETY_HOME,
            ApplicationConstants::LOAN_APP => RouteServiceProvider::LOAN_APP_HOME,
            ApplicationConstants::NMWRRS => RouteServiceProvider::NMWRRS_HOME,
            ApplicationConstants::NMWRRS_EXTERNAL => RouteServiceProvider::NMWRRS_HOME_EXTERNAL,
            ApplicationConstants::PLANT_LIST => RouteServiceProvider::PLANT_LIST_HOME,
            ApplicationConstants::E_DOCKET => RouteServiceProvider::E_DOCKET_HOME,
            ApplicationConstants::WATER_MANAGEMENT => RouteServiceProvider::WATER_MANAGEMENT_HOME,
            ApplicationConstants::WATER_MANAGEMENT_PPP => RouteServiceProvider::WATER_MANAGEMENT_PPP_HOME,
            ApplicationConstants::NMWATER => RouteServiceProvider::NMWATER,
            ApplicationConstants::RBTS => RouteServiceProvider::RBTS_HOME,
            default => RouteServiceProvider::HOME,
        };
        log::info("Navigating to " . $path);
        return $path;
    }

    /**
     * Create the PDF using mPDF
     * @param $html
     * @param string $mode - I for View,  D for download
     * @param $borrower
     * @param $contract
     * @param string $document
     * @throws MpdfException
     */
    public function createPDF($html, string $mode, $borrower, $contract, string $document)
    {
        $now = new DateTime();
        $html = $html->render();
        $pdfTemplate = new PDFTemplateStateLetterhead();
        $mpdf = $pdfTemplate->getBasicPDF($borrower, $document);
        $mpdf->WriteHTML($html);
        $mpdf->Output($document . "_" . $borrower->ID . "_" . $contract->ID . "_" . $now->format('Ymd') . ".pdf", $mode);
    }


    /**
     * Transform collection Counties into a string of
     * <county1> County or <county1> and <county2> Counties
     * @param Collection $counties
     * @return string
     */
    public function getCountiesString(Collection $counties): string
    {
        $countyString = "";
        for ($i = 0; $i < $counties->count(); $i++) {
            if ($i > 0) {
                $countyString = $countyString . " and ";
            }
            $countyString = $countyString . ucwords(strtolower($counties[$i]->CountyName));
        }
        return $counties->count() > 1 ? $countyString . " Counties" : $countyString . " County";
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function getKey(): string
    {
        $client = new Client();
        $response = $client->request('GET', 'http://coderepo:8088');
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            throw new Exception ("Error getting key for env");
        }
        $body = $response->getBody()->getContents();
        $jsonObj = json_decode($body);
        return substr($jsonObj->{'data'}, 0, 32);
    }

    /**
     * Encrypt a message
     *
     * @param string $message - message to encrypt
     * @return string
     * @throws SodiumException
     * @throws Exception
     * @throws GuzzleException
     */
    function safeEncrypt(string $message): string
    {
        $key = $this->getKey();
        $nonce = random_bytes(
            SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        );

        $cipher = base64_encode(
            $nonce .
            sodium_crypto_secretbox(
                $message,
                $nonce,
                $key
            )
        );
        sodium_memzero($message);
        sodium_memzero($key);
        return $cipher;
    }

    /**
     * Decrypt a message
     *
     * @param string $encrypted - message encrypted with safeEncrypt()
     * @return string
     * @throws SodiumException
     * @throws Exception|GuzzleException
     */
    function safeDecrypt(string $encrypted): string
    {
        $key = $this->getKey();
        $decoded = base64_decode($encrypted);
        if ($decoded === false) {
            throw new Exception('encoding failed');
        }
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new Exception('Scream bloody murder, the message was truncated');
        }
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        $plain = sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $this->getKey()
        );
        if ($plain === false) {
            throw new Exception('the message was tampered with in transit');
        }
        sodium_memzero($ciphertext);
        sodium_memzero($key);
        return $plain;
    }

    /**
     * Handle properties
     * if property begins with ENC_PREFIX decrypt else return property
     * @throws GuzzleException
     * @throws SodiumException
     */
    public function handleProperty(string $property): string
    {
        return (substr($property, 0, 4) == env('ENC_PREFIX')) ?
            $this->safeDecrypt(substr($property, 4)) : $property;
    }


    /**
     * Get a list of month names in order
     * @return array
     */
    public function getMonthNames(): array
    {
        $months = [];
        for ($i = 1; $i < 13; $i++) {
            $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
        }
        return $months;
    }


    /**
     * Get a list of month names in order
     * @param string|null $monthName <p>The name or abbreviation of the month</p>
     * @return string|null <p>A two character string for the month's number 01 - 12
     *                        or null if parameter is passed as null</p>
     */
    public function getMonthNumbers(?string $monthName = null): ?string
    {
        if (!$monthName) {
            return null;
        }
        return str_pad(date('n', strtotime($monthName)), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Return the fiscal year:
     * January - June is the current year
     * July - December is the current year +1
     * @param DateTime|null $dateTime - if null, now() will be used for date
     * @param bool $inverse - returns calendar year from fiscal year
     * @return int|string
     */
    public function getFiscalYear(?DateTime $dateTime = new DateTime(), bool $inverse = false): int|string
    {
        $month = $dateTime->format('m');
        $year = $dateTime->format('Y');
        if ($month > 6) {
            return $inverse ? $year - 1 : $year + 1;
        }
        return $year;
    }

    /**
     * @param int $year - 4 year format
     * @param int|null $quarter - 1,2,3,4 else returns full fiscal year start and end
     * @return DateTime[] - start and end dates of quarter (in calendar year format)
     * @throws Exception
     */
    public function getQuarterDates(int $year, bool $isFiscalYear = true, int $quarter = null): array
    {
        if ($isFiscalYear) {
            return match ($quarter) {
                1 => [new DateTime($year - 1 . ApplicationConstants::FIRST_QUARTER_DATES[0]),
                    new DateTime($year - 1 . ApplicationConstants::FIRST_QUARTER_DATES[1])],
                2 => [new DateTime($year - 1 . ApplicationConstants::SECOND_QUARTER_DATES[0]),
                    new DateTime($year - 1 . ApplicationConstants::SECOND_QUARTER_DATES[1])],
                3 => [new DateTime($year . ApplicationConstants::THIRD_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::THIRD_QUARTER_DATES[1])],
                4 => [new DateTime($year . ApplicationConstants::FOURTH_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::FOURTH_QUARTER_DATES[1])],
                default => [new DateTime($year - 1 . ApplicationConstants::FIRST_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::FOURTH_QUARTER_DATES[1])],
            };
        } else {
            return match ($quarter) {
                1 => [new DateTime($year . ApplicationConstants::THIRD_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::THIRD_QUARTER_DATES[1])],
                2 => [new DateTime($year . ApplicationConstants::FOURTH_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::FOURTH_QUARTER_DATES[1])],
                3 => [new DateTime($year . ApplicationConstants::FIRST_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::FIRST_QUARTER_DATES[1])],
                4 => [new DateTime($year . ApplicationConstants::SECOND_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::SECOND_QUARTER_DATES[1])],
                default => [new DateTime($year . ApplicationConstants::THIRD_QUARTER_DATES[0]),
                    new DateTime($year . ApplicationConstants::SECOND_QUARTER_DATES[1])],
            };
        }
    }

    /**
     * Get first day of the Year or fiscal Year
     * @param int $year
     * @param bool $isFiscalYear
     * @param int $addYears
     * @return DateTime
     * @throws Exception
     */
    public function getStartOfYear(int $year, bool $isFiscalYear = false, int $addYears = 0): DateTime
    {
        return $isFiscalYear ? new DateTime('1st July' . ($year + $addYears - 1)) :
            new DateTime('1st January' . ($year + $addYears));
    }


    /**
     * Get Last day of the Year or fiscal Year
     * @param int $year
     * @param bool $isFiscalYear
     * @param int $addYears
     * @return DateTime
     * @throws Exception
     *
     *
     * Update: Corrected the date format from '30st June' to '30th June'.
     *
     *
     */
    public function getEndOfYear(int $year, bool $isFiscalYear = false, int $addYears = 0): DateTime
    {
        return $isFiscalYear?new DateTime('30th June'.($year + $addYears)):
            new DateTime('31st December' . ($year + $addYears));
    }

    /**
     * Get dateTime format for a given date time with the option to modify the date
     * @param DateTime $inputDateTime
     * @param string $format
     * @param string|null $modifyValue
     * @param string|null $modifyUnit
     * @return string
     */
    public function getDateTimeFormat(DateTime $inputDateTime, string $format,
                                      string   $modifyValue = null, string $modifyUnit = null): string
    {
        if ($modifyValue && $modifyUnit) {
            return $inputDateTime->modify($modifyValue . ' ' . $modifyUnit)->format($format);
        } else {
            return $inputDateTime->format($format);
        }
    }

    /**
     * Get the last five fiscal year numbers as string
     * @param int $startYear - four digit year for starting point
     * @param int $numberOfYears - how many years plus or minus
     * @param bool $isPast - true returns previous years, false is future
     * @return array|string
     */
    public function getPreviousOrNextFiscalYears(int $startYear, int $numberOfYears, bool $isPast): array|string
    {
        $years = array();
        $currentFY = $startYear;
        for ($i = 1; $i <= $numberOfYears; $i++) {
            $years[] = $isPast ? $currentFY - $i : $currentFY + $i;
        }
        return $years;
    }

    /**
     * Convert a boolean value to a string based on provided text values.
     *
     * @param bool|null $inputBoolean The boolean value to convert to a string.
     * @param string $yesText The string to return if the input value is true. Defaults to 'Yes'.
     * @param string $noText The string to return if the input value is false. Defaults to 'No'.
     * @param string|null $nullText The string to return if the input value is null. Defaults to 'Null'.
     * @return string|null The string representation of the boolean value.
     */
    function convertBooleanToString(bool   $inputBoolean = null, string $yesText = ApplicationConstants::YES,
                                    string $noText = ApplicationConstants::NO, string $nullText = null): ?string
    {
        if ($inputBoolean === true) {
            return $yesText;
        } elseif ($inputBoolean === false) {
            return $noText;
        } else {
            return $nullText;
        }
    }

    /**
     * Convert an integer value to a string based on a provided array of [int, string] pairs.
     * @param int|null $inputInteger The integer value to convert to a string.
     * @param array $textArray An array of [int, string] pairs where the int is the value to match and the string is the value to return.
     * @return string|null The string representation of the integer value.
     */
    function convertIntToString(?int $inputInteger, array $textArray): ?string
    {
        return key_exists($inputInteger,$textArray)?$textArray[$inputInteger]:null;
    }


    /**
     * @param $easting
     * @param $northing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogleMaps($easting, $northing)
    {
        // Constants for UTM Zone 13 in New Mexico
        $utmZone = 13;

        // Semi-major and semi-minor axes for the GRS 80 ellipsoid (NAD83)
        $a = 6378137.0; // Semi-major axis
        $f = 1 / 298.257222101; // Flattening

        // Central meridian of UTM Zone 13 in radians
        $centralMeridian = deg2rad(-105.0);

        // False easting and northing for UTM Zone 13
        $falseEasting = 500000.0;
        $falseNorthing = 0.0;

        // Convert UTM coordinates to meters
        $x = $easting - $falseEasting;
        $y = $northing - $falseNorthing;

        // Calculate the scale factor
        $k0 = 0.9996;

        // Calculate the longitude
        $lambda0 = $centralMeridian;
        $esq = 2 * $f - $f * $f;
        $e1 = (1 - sqrt(1 - $esq)) / (1 + sqrt(1 - $esq));
        $M = $y / $k0;
        $mu = $M / ($a * (1 - $esq * (1 / 4 + $esq * (3 / 64 + 5 * $esq / 256))));
        $phi1Rad = $mu + (3 * $e1 / 2 - 27 * $e1 * $e1 * $e1 / 32) * sin(2 * $mu)
            + (21 * $e1 * $e1 / 16 - 55 * $e1 * $e1 * $e1 * $e1 / 32) * sin(4 * $mu)
            + (151 * $e1 * $e1 * $e1 / 96) * sin(6 * $mu);
        $phi1 = rad2deg($phi1Rad);
        $N1 = $a / sqrt(1 - $esq * sin($phi1Rad) * sin($phi1Rad));
        $T1 = tan($phi1Rad) * tan($phi1Rad);
        $C1 = $esq * cos($phi1Rad) * cos($phi1Rad);
        $R1 = $a * (1 - $esq) / pow(1 - $esq * sin($phi1Rad) * sin($phi1Rad), 1.5);
        $D = $x / ($N1 * $k0);

        $latRad = $phi1Rad - ($N1 * tan($phi1Rad) / $R1) * ($D * $D / 2 - (5 + 3 * $T1 + 10 * $C1 - 4 * $C1 * $C1 - 9 * $esq) * $D * $D * $D * $D / 24
                + (61 + 90 * $T1 + 298 * $C1 + 45 * $T1 * $T1 - 252 * $esq - 3 * $C1 * $C1) * $D * $D * $D * $D * $D * $D / 720);
        $lat = rad2deg($latRad);
        $lngRad = $lambda0 + ($D - (1 + 2 * $T1 + $C1) * $D * $D * $D / 6
                + (7 + 14 * $T1 + 13 * $C1 - 3 * $T1 * $T1) * $D * $D * $D * $D * $D / 120) / cos($phi1Rad);
        $lng = rad2deg($lngRad);

        // Calculate the latitude and round it to 5 decimal places
        $lat = round($lat, 5);

        // Calculate the longitude and round it to 5 decimal places
        $lng = round($lng, 5);


        // Build the Google Maps URL
        $googleMapsUrl = "https://www.google.com/maps?q=$lat,$lng";

        // Redirect to Google Maps
        return redirect()->away($googleMapsUrl);

    }




    /**
     * Update the audit table with information about the model update.
     *
     * This method creates a new record in the audit table to track updates
     * made to a specific model. It captures information such as the model's ID,
     * table name, activity type (update), user who performed the update,
     * date and time of the update, as well as the changes made.
     * TODO: change audit to be a class passed in as parameter
     * @param mixed $model - any model class to be audited
     * @return void
     */
    public function updateAuditTable(mixed $model): void
    {
        $audit = new Audit();
        $audit->table_id = $model->id;
        $audit->table_name = $model->getTable();
        $audit->Activity = 'Update';
        $audit->DoneBy = auth()->user()?->name[0]??'unknown';
        $audit->Date_Time =now();
        $dirtyAttributes = $model->getDirty();
        $originalValues = $model->getOriginal();

        $changes = [];

        foreach ($dirtyAttributes as $attribute => $newValue) {
            $originalValue = $originalValues[$attribute] ?? null;

            $changes[$attribute] = [
                'original' => $originalValue,
                'new' => $newValue,
            ];
        }

        $jsonChanges = json_encode($changes, JSON_PRETTY_PRINT);
        $audit->trans_info =$jsonChanges;

        $audit->save();
    }


    /**
     * Get the username from the LDAP.
     *
     */
    public function getUsername()
    {
        return env('LDAP_ENGINE') === 'MicrosoftAD' ? auth()->user()->samaccountname[0] : auth()->user()->uid[0];
    }

    /**
     * Authenticate service Requests
     * @return JsonResponse|void
     */
    public function authenticate(Request $request)
    {
        // Check if the request is authenticated
        if (!$request->hasHeader('Authorization')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate the JWT token
        $jwt = $request->header('Authorization');
        try {
            $token = JWT::decode($jwt, env('JWT_SECRET'), ['HS256']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Check if the user has the required role to consume this service
        if (!in_array('maintenance-consumer', $token->roles)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
    }

    /**
     * Convert a constant string to camelCase.
     *
     * @param string $str The constant string to convert.
     * @return string The converted camelCase string.
     */
    function constantToCamelCase(string $str): string
    {
        $words = explode('_', $str);
        $newStr = '';

        foreach ($words as $word) {
            $newStr .= ucfirst(strtolower($word));
        }
        return lcfirst($newStr);
    }

    /**
     * Get an array of years based on the start year, number of years, and padding option.
     *
     * @param DateTime $start The start date from which to calculate the years.
     * @param int $years The number of years to include in the array.
     * @param bool $pad Whether to pad the years on both sides of the start year.
     * @return array An array of integers representing the years.
     */
    public function getYearsArray(DateTime $start, int $years, bool $pad = true): array
    {
        $startYear = (int)$start->format('Y');
        $result = [];

        if ($pad) {
            for ($i = -$years; $i <= $years; $i++) {
                $result[] = $startYear + $i;
            }
        } else {
            for ($i = min(0,$years); $i <= max($years,0); $i++) {
                $result[] = $startYear + $i;
            }
        }
        return $result;
    }


    public function getModelName($model): string
    {
        return Str::snake(class_basename($model));
    }


    /**
     * Sets session variables for search route and search text parameters.
     *
     * This method is used to update session variables storing the search route and
     * search text parameters based on the provided values. It clears any existing
     * session data for these parameters and sets new values retrieved from the
     * method arguments.
     *
     * @param mixed $searchText The search text or parameters to be stored in session.
     * @param string $searchRoute The route identifier for the search action.
     *
     * @return void
     */
    public function handleSearchVariables(string $searchText, string $searchRoute): void
    {
        session()->forget('search-route-params');
        session()->put('search-route-params', $searchRoute);
        session()->forget('search-text-params');
        session()->put('search-text-params', $searchText);
    }
}
