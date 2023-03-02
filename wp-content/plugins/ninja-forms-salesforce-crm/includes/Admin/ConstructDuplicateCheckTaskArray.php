<?php

namespace NinjaForms\Salesforce\Admin;

/**
 * Construct Task details as an array
 * 
 * Used to create a task that notifies user of a matched person in Salesforce
 */
class ConstructDuplicateCheckTaskArray
{

    const DEFAULT_TEXT = [
        'description_intro' => 'A recent form submission has a possible duplication in the following Object: ',
        'field_check' => 'Please check this field: ',
        'duplicate_value' => 'for a duplicate value: ',
        'subject' =>  'Duplicate found from web form submission',

    ];

    /** @var array */
    protected $parameterArray;

    /** @var array */
    protected $translatedText;

    /** @var string */
    protected $dateInterval;

    /**
     * Salesforce date format
     *
     * @var string
     */
    protected $dateFormat;

    protected $taskFieldsArray;

    /**
     * Construct Task details as an array
     *
     * @param array $parameterArray
     * @param array $translatedText keys:'description_intro' 'field_check' 'duplicate_value' 'subject'
     * @param string $dateInterval Default is '1 second'
     * @param string|null $dateFormat
     */
    public function __construct(
        array $parameterArray,
        array $translatedText,
        ?string $dateInterval = '1 second', 
        ?string $dateFormat = 'Y-m-d'
    ) {
        $this->parameterArray = $parameterArray;
        
        $this->translatedText = array_merge(self::DEFAULT_TEXT, $translatedText);
        
        $this->dateInterval = $dateInterval;

        $this->dateFormat = $dateFormat;
    }

    /**
     * Construct Task array
     * 
     * keys: 'Subject' 'Description' 'ActivityDate'
     *
     * @return array
     */
    public function handle(): array
    {
        $subject = $this->translatedText['subject'];
        $descriptionText = $this->constructDescription();
        $date = $this->calculateDate();
        
        $return=[
            'Subject' => $subject,
            'Description' => $descriptionText,
            'ActivityDate' => $date
        ];

        return $return;
    }


    /**
     * Construct description
     *
     * @return string
     */
    protected function constructDescription(): string
    {
        $description_text = $this->translatedText['description_intro']
            . $this->getParameter('object_name') . '.  '
            . $this->translatedText['field_check']
            . $this->getParameter('field_name') . ' '
            . $this->translatedText['duplicate_value']
            . $this->getParameter('field_value');

        return $description_text;
    }

    /**
     * Calculate date from date interval
     *
     * @return string
     */
    protected function calculateDate(): string
    {
        $date = new \DateTime(); // get a timestamp

        $dateIntervalObject = \date_interval_create_from_date_string($this->dateInterval);
        
        if($dateIntervalObject){

            date_add($date, $dateIntervalObject); // delay task by interval amount
        }

        $formatted_date = $date->format($this->dateFormat); // format the date for Salesforce

        return $formatted_date;
    }

    /**
     * Extract keyed parameter from parameter array
     *
     * @return string
     */
    protected function getParameter(string $key)
    {
        $return = 'Parameter is not set correctly';

        if (isset($this->parameterArray[$key])) {
            $return = $this->parameterArray[$key];
        }

        return $return;
    }

}
