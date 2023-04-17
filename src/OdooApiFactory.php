<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fab IT Sylius Odoo Core Plugin to newer
 * versions in the future.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://www.fabitsolutions.in/ and write us
 * an email on contact@fabitsolutions.in
 *
 * @category  Fabitsolutions
 * @package   fabit/sylius-odoo-product-plugin
 * @author    contact@fabitsolutions.in
 * @copyright 2023 Fab IT Solutions
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Fabit\SyliusOdooCorePlugin;


use Fabit\SyliusOdooCorePlugin\Helper\XmlRpcApiHelper;
use Psr\Log\LoggerInterface;

abstract class OdooApiFactory extends XmlRpcApiHelper
{
    public function __construct(LoggerInterface $logger, array $params, array $config = [])
    {
        parent::__construct($logger, $params, $config);
        $this->logger->debug('execution for :'.date('Y:m:d h:i:s'));
    }

    abstract public function processData($data);

    final public function setModule(string $module): void
    {
        $this->module = $module;
        $this->logger->debug('model: '.$this->module);
    }

    final public function setAction(string $action): void
    {
        $this->action = $action;
        $this->logger->debug('action: '.$this->action);
    }

    final public function setFilter(array $filter): void
    {
        $this->filter = $filter;
        $this->logger->debug('filter: '.json_encode($this->filter));
    }
    
    final public function addStringFilter(string $field, string $operation, string $value): void
    {
        if(empty($this->filter)) {
            $this->filter = [[]];
        }
        
        $notFound = true;
        for($index = 0; $index < count($this->filter[0]); $index ++) {
            if(isset($this->filter[0][$index][0]) && $this->filter[0][$index][0] == $field) {
                $this->filter[0][$index] = [$field, $operation, $value];
                $notFound = false;
            }
        }
        
        if($notFound == true) {
            $this->filter[0][] = [$field, $operation, $value];
        }
        
        $this->logger->debug('filter Updated: '.json_encode($this->filter));
    }
    
    final public function addArrayFilter(string $field, string $operation, array $value): void
    {
        if(empty($this->filter)) {
            $this->filter = [[]];
        }
        
        $notFound = true;
        for($index = 0; $index < count($this->filter[0]); $index ++) {
            if(isset($this->filter[0][$index][0]) && $this->filter[0][$index][0] == $field) {
                $this->filter[0][$index] = [$field, $operation, $value];
                $notFound = false;
            }
        }
        
        if($notFound == true) {
            $this->filter[0][] = [$field, $operation, $value];
        }
        
        $this->logger->debug('filter Updated: '.json_encode($this->filter));
    }

    final public function setFields(array $fieldsArray, int $offset =0 ,int $limit=5): void
    {
        $this->fields = $fieldsArray;
        $this->logger->debug('offset:'.$offset.", limit:".$limit);
    }
    
    final public function setLang(?string $lang): void
    {
        if(!empty($lang)) {
            if(empty($this->fields['context'])) {
                $this->fields['context'] = [];
            }
            
            $this->fields['context']['lang'] =$lang;
        }
        
        $this->logger->debug('lang:'.$lang);
    }

    final public function setOffsetLimit(int $offest = 0, int $limit = 5): void
    {
        $this->fields['offset'] = $offest;
        $this->fields['limit'] = $limit;
        $this->setFields($this->fields, $offest, $limit);
    }
}
