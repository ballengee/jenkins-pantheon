<?php

/**
 * Manages updates to the communication data log
 * 
 * Retrieves existing db array for starting log; overwrites or appends
 * new communication data; writes log to db
 *
 * @author stuartlb3
 */
class SalesforceCommData {

    
    /**
     * Array of communication data
     * @var array
     */
    protected $comm_data;

    /**
     * Removes existing data in key and initializes empty array
     * @param string $key
     */
    public function resetKey($key){
        
        unset($this->comm_data[$key]);
    }
    
    /**
     * Appends an entry to the indexed array in a given key
     * @param string $key Comm Data key storing the data
     * @param mixed $entry Value to be appended as array element
     */
    public function append($key,$entry){
        
        $this->comm_data[$key][]=$entry;
    }
    
    /**
     * Replaces existing value with a new value
     * @param string $key
     * @param mixed $entry New value to be stored in key
     */
    public function set($key,$entry){
        
        $this->comm_data[$key]=$entry;
    }
    
    /**
     * Retrieves existing CommData from db
     * 
     * If incoming value is not an array, sets CommData as empty array
     * @param string $db_key WP options table key storing data
     */
    public function initializeCommData($db_key){
        
        $db_comm_data = get_option($db_key);
        
        if(!is_array( $db_comm_data )){
            
            $this->comm_data = array();
        }else{
            
            $this->comm_data = $db_comm_data;
        }
    }
    
    /**
     * Stores CommData array in WP options table under given key
     * @param string $db_key WP options table key storing data
     */
    public function storeCommData($db_key){
        
        update_option($db_key,$this->comm_data);
    }
}
