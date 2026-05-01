<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;

class Language implements OptionSourceInterface
{
    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        ApiHelper $apiHelper
    ) {
        $this->apiHelper = $apiHelper;
    }

    /**
     * Static fallback: all WhatsApp-supported languages (code => name)
     */
    private const STATIC_LANGUAGES = [
        'af'     => 'Afrikaans',
        'sq'     => 'Albanian',
        'ar'     => 'Arabic',
        'az'     => 'Azerbaijani',
        'bn'     => 'Bengali',
        'bg'     => 'Bulgarian',
        'ca'     => 'Catalan',
        'zh_CN'  => 'Chinese (CHN)',
        'zh_HK'  => 'Chinese (HKG)',
        'zh_TW'  => 'Chinese (TAI)',
        'hr'     => 'Croatian',
        'cs'     => 'Czech',
        'da'     => 'Danish',
        'nl'     => 'Dutch',
        'en'     => 'English',
        'en_GB'  => 'English (UK)',
        'en_US'  => 'English (US)',
        'en_GH'  => 'English (GHA)',
        'en_IE'  => 'English (IRL)',
        'en_IN'  => 'English (IND)',
        'et'     => 'Estonian',
        'fil'    => 'Filipino',
        'fi'     => 'Finnish',
        'fr'     => 'French',
        'ka'     => 'Georgian',
        'de'     => 'German',
        'de_AT'  => 'German (AUT)',
        'el'     => 'Greek',
        'gu'     => 'Gujarati',
        'ha'     => 'Hausa',
        'he'     => 'Hebrew',
        'hi'     => 'Hindi',
        'hu'     => 'Hungarian',
        'id'     => 'Indonesian',
        'ga'     => 'Irish',
        'it'     => 'Italian',
        'ja'     => 'Japanese',
        'kn'     => 'Kannada',
        'kk'     => 'Kazakh',
        'rw_RW'  => 'Kinyarwanda',
        'ko'     => 'Korean',
        'ky_KG'  => 'Kyrgyz (Kyrgyzstan)',
        'lo'     => 'Lao',
        'lv'     => 'Latvian',
        'lt'     => 'Lithuanian',
        'mk'     => 'Macedonian',
        'ms'     => 'Malay',
        'ml'     => 'Malayalam',
        'mr'     => 'Marathi',
        'nb'     => 'Norwegian',
        'fa'     => 'Persian',
        'prs_AF' => 'Dari',
        'pl'     => 'Polish',
        'pt_BR'  => 'Portuguese (BR)',
        'pt_PT'  => 'Portuguese (POR)',
        'pa'     => 'Punjabi',
        'ro'     => 'Romanian',
        'ru'     => 'Russian',
        'sr'     => 'Serbian',
        'si'     => 'Sinhala',
        'sk'     => 'Slovak',
        'sl'     => 'Slovenian',
        'es'     => 'Spanish',
        'es_AR'  => 'Spanish (ARG)',
        'es_CL'  => 'Spanish (CHL)',
        'es_CO'  => 'Spanish (COL)',
        'es_MX'  => 'Spanish (MEX)',
        'es_PA'  => 'Spanish (PAN)',
        'es_PE'  => 'Spanish (PER)',
        'es_ES'  => 'Spanish (SPA)',
        'es_UY'  => 'Spanish (URY)',
        'sw'     => 'Swahili',
        'sv'     => 'Swedish',
        'ta'     => 'Tamil',
        'te'     => 'Telugu',
        'th'     => 'Thai',
        'tr'     => 'Turkish',
        'uk'     => 'Ukrainian',
        'ur'     => 'Urdu',
        'uz'     => 'Uzbek',
        'vi'     => 'Vietnamese',
        'be_BY'  => 'Belarusian',
        'zu'     => 'Zulu',
    ];

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['label' => '-- Select Language --', 'value' => '']];
        foreach (self::STATIC_LANGUAGES as $code => $name) {
            $options[] = [
                'label' => $name . ' (' . $code . ')',
                'value' => $code
            ];
        }

        return $options;
    }
}
