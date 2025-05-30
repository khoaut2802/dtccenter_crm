<?php

namespace Webkul\Core;

use Carbon\Carbon;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Repositories\CountryRepository;
use Webkul\Core\Repositories\CountryStateRepository;


class Core
{
    /**
     * The Krayin version.
     *
     * @var string
     */
    const KRAYIN_VERSION = '2.1.2';

    /**
     * Currency symbols mapping
     *
     * @var array
     */
    protected $currencySymbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'CNY' => '¥',
        'KRW' => '₩',
        'VND' => '₫',
        'THB' => '฿',
        'SGD' => 'S$',
        'MYR' => 'RM',
        'IDR' => 'Rp',
        'PHP' => '₱',
        'INR' => '₹',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'PLN' => 'zł',
        'CZK' => 'Kč',
        'HUF' => 'Ft',
        'RUB' => '₽',
        'BRL' => 'R$',
        'MXN' => '$',
        'ARS' => '$',
        'CLP' => '$',
        'COP' => '$',
        'PEN' => 'S/',
        'TRY' => '₺',
        'ZAR' => 'R',
        'EGP' => 'E£',
        'AED' => 'د.إ',
        'SAR' => '﷼',
        'QAR' => '﷼',
        'KWD' => 'د.ك',
        'BHD' => '.د.ب',
        'OMR' => '﷼',
        'JOD' => 'د.ا',
        'LBP' => '£',
        'ILS' => '₪',
        'PKR' => '₨',
        'BDT' => '৳',
        'LKR' => '₨',
        'NPR' => '₨',
        'MMK' => 'K',
        'LAK' => '₭',
        'KHR' => '៛',
    ];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected CountryRepository $countryRepository,
        protected CoreConfigRepository $coreConfigRepository,
        protected CountryStateRepository $countryStateRepository
    ) {}

    /**
     * Get the version number of the Krayin.
     *
     * @return string
     */
    public function version()
    {
        return static::KRAYIN_VERSION;
    }

    /**
     * Retrieve all timezones.
     */
    public function timezones(): array
    {
        $timezones = [];

        foreach (timezone_identifiers_list() as $timezone) {
            $timezones[$timezone] = $timezone;
        }

        return $timezones;
    }

    /**
     * Retrieve all locales.
     */
    public function locales(): array
    {
        $options = [];

        foreach (config('app.available_locales') as $key => $title) {
            $options[] = [
                'title' => $title,
                'value' => $key,
            ];
        }

        return $options;
    }

    /**
     * Retrieve all countries.
     *
     * @return \Illuminate\Support\Collection
     */
    public function countries()
    {
        return $this->countryRepository->all();
    }

    /**
     * Returns country name by code.
     */
    public function country_name(string $code): string
    {
        $country = $this->countryRepository->findOneByField('code', $code);

        return $country ? $country->name : '';
    }

    /**
     * Returns state name by code.
     */
    public function state_name(string $code): string
    {
        $state = $this->countryStateRepository->findOneByField('code', $code);

        return $state ? $state->name : $code;
    }

    /**
     * Retrieve all country states.
     *
     * @return \Illuminate\Support\Collection
     */
    public function states(string $countryCode)
    {
        return $this->countryStateRepository->findByField('country_code', $countryCode);
    }

    /**
     * Retrieve all grouped states by country code.
     *
     * @return \Illuminate\Support\Collection
     */
    public function groupedStatesByCountries()
    {
        $collection = [];

        foreach ($this->countryStateRepository->all() as $state) {
            $collection[$state->country_code][] = $state->toArray();
        }

        return $collection;
    }

    /**
     * Retrieve all grouped states by country code.
     *
     * @return \Illuminate\Support\Collection
     */
    public function findStateByCountryCode($countryCode = null, $stateCode = null)
    {
        $collection = [];

        $collection = $this->countryStateRepository->findByField([
            'country_code' => $countryCode,
            'code'         => $stateCode,
        ]);

        if (count($collection)) {
            return $collection->first();
        } else {
            return false;
        }
    }

    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return mixed
     */
    public function getSingletonInstance($className)
    {
        static $instances = [];

        if (array_key_exists($className, $instances)) {
            return $instances[$className];
        }

        return $instances[$className] = app($className);
    }

    /**
     * Format date
     *
     * @return string
     */
    public function formatDate($date, $format = 'd M Y h:iA')
    {
        return Carbon::parse($date)->format($format);
    }

    /**
     * Week range.
     *
     * @param  string  $date
     * @param  int  $day
     * @return string
     */
    public function xWeekRange($date, $day)
    {
        $ts = strtotime($date);

        if (! $day) {
            $start = (date('D', $ts) == 'Sun') ? $ts : strtotime('last sunday', $ts);

            return date('Y-m-d', $start);
        } else {
            $end = (date('D', $ts) == 'Sat') ? $ts : strtotime('next saturday', $ts);

            return date('Y-m-d', $end);
        }
    }

    /**
     * Return currency symbol from currency code.
     *
     * @param  string  $code
     * @return string
     */
    public function currencySymbol($code)
    {
        $code = strtoupper($code);
        
        return $this->currencySymbols[$code] ?? $code;
    }

    /**
     * Format price with base currency symbol. This method also give ability to encode
     * the base currency symbol and its optional.
     *
     * @param  float  $price
     * @return string
     */
    public function formatBasePrice($price)
    {
        if (is_null($price)) {
            $price = 0;
        }

        $currency = config('app.currency');
        $symbol = $this->currencySymbol($currency);
        
        // Determine decimal places based on currency
        $decimals = $this->getCurrencyDecimals($currency);
        
        // Format the number
        $formattedPrice = number_format($price, $decimals, '.', ',');
        
        // Some currencies place symbol after the amount
        $symbolAfterCurrencies = ['VND', 'EUR', 'SEK', 'NOK', 'DKK', 'CZK', 'HUF', 'PLN'];
        
        if (in_array($currency, $symbolAfterCurrencies)) {
            return $formattedPrice . ' ' . $symbol;
        }
        
        return $symbol . ' ' . $formattedPrice;
    }

    /**
     * Get decimal places for currency formatting
     *
     * @param  string  $currency
     * @return int
     */
    protected function getCurrencyDecimals($currency)
    {
        // Currencies that typically don't use decimal places
        $noDecimalCurrencies = ['JPY', 'KRW', 'VND', 'IDR', 'CLP', 'KMF', 'DJF', 'GNF', 'ISK', 'PYG', 'RWF', 'UGX', 'VUV', 'XAF', 'XOF', 'XPF'];
        
        return in_array(strtoupper($currency), $noDecimalCurrencies) ? 0 : 2;
    }

    /**
     * Get the config field.
     */
    public function getConfigField(string $fieldName): ?array
    {
        return system_config()->getConfigField($fieldName);
    }

    /**
     * Retrieve information for configuration.
     */
    public function getConfigData(string $field): mixed
    {
        return system_config()->getConfigData($field);
    }
}