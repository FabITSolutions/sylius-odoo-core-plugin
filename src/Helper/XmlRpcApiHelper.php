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

namespace Fabit\SyliusOdooCorePlugin\Helper;


use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ripcord\Client\Client;
use Ripcord\Providers\Laravel\Ripcord;
use Ripcord\Ripcord as RipcordBase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

abstract class XmlRpcApiHelper extends Ripcord
{
    /** @var LoggerInterface */
    public $logger;

    /** @var Client */
    public $common;

    /** @var FilesystemAdapter */
    public $fileCache;

    public $module;

    public $action;

    public $filter;

    /** @var array */
    public $fields = [];

    public $data;

    /**
     * Ripcord constructor.
     *
     * @param LoggerInterface $logger
     * @param array $params
     * @param $config array
     * @throws InvalidArgumentException
     */
    public function __construct(LoggerInterface $logger, array $params, array $config = [])
    {
        $this->logger = $logger;
        $this->fileCache = new FilesystemAdapter('', 604800) ;
        parent::__construct($config);
        $this->connect();
        $this->setModule($params['module']);
        $this->setAction($params['action']);
        if(!in_array($params['action'], ['unlink', 'write', 'create'])) {
            $this->setFields($params['fieldsArray'], $params['offset'], $params['limit']);
            $this->setFilter($params['filter']);
        }
    }

    /**
     * Create connection.
     * @throws InvalidArgumentException
     */
    final public function connect(): void
    {
        $this->getCommon();
        $this->getUid();
        $this->getClient();
    }

    /**
     * get common
     */
    final public function getCommon(): void
    {
        try{
            $this->common = RipcordBase::client(
                "$this->url/xmlrpc/2/common",
                null,new Stream());
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->removeUid();
        }

    }

    /**
     * get uid
     * @throws InvalidArgumentException
     */
    final public function getUid(): void
    {
        $this->uid = $this->fileCache->get('odoo_uid_key', function (ItemInterface $item) {
            $computedValue = '';
            try{
                $item->expiresAfter(604800);
                $computedValue = $this->common->authenticate($this->db, $this->username, $this->password, []);
                $this->logger->debug(json_encode($computedValue));
            } catch (\Exception $ex) {
                $this->logger->error($ex->getMessage());
                $this->removeUid();
            } finally {
                return $computedValue;
            }
        });
    }

    final public function removeUid(): void
    {
        $this->fileCache->delete('odoo_uid_key');
    }

    /**
     * get client
     */
    final public function getClient(): void
    {
        try{
            $this->client = RipcordBase::client("$this->url/xmlrpc/2/object", null , new Stream());
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->removeUid();
        }
    }

    final public function getData($offset = null, $limit = 5)
    {
        try {
            if ($offset !== null){
                $this->setOffsetLimit($offset, $limit);
            }
            $data = $this->execute();
            return $this->processData($data);
        }
        catch (\Exception $e){
            return 1;
        }
    }
    
    final public function createData(array $fields)
    {
        try {
            $data = $this->create($fields);
            return $this->processData($data);
        }
        catch (\Exception $e){
            return 1;
        }
    }
    
    final public function writeData(int $id, array $fields)
    {
        try {
            $data = $this->write($id, $fields);
            return $this->processData($data);
        }
        catch (\Exception $e){
            return 1;
        }
    }
    
    final public function unlinkData(string $id, array $fields)
    {
        try {
            $data = $this->unlink($id, $fields);
            return $this->processData($data);
        }
        catch (\Exception $e){
            return 1;
        }
    }

    abstract public function processData($data);

    abstract public function setModule(string $module): void;

    abstract public function setAction(string $action): void;

    abstract public function setFilter(array $filter): void;

    abstract public function setOffsetLimit(int $offest = 0, int $limit = 5): void;

    abstract public function setFields(array $fieldsArray, int $offset = 0 , int $limit = 5): void;

    final public function execute()
    {
        try{
            return $this->client->execute_kw(
                $this->db, $this->uid, $this->password,
                $this->module, $this->action,
                $this->filter,
                $this->fields
            );
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->removeUid();
            return false;
        }
    }
    
    final public function create(array $fields)
    {
        try{
            return $this->client->execute_kw(
                $this->db, $this->uid, $this->password,
                $this->module, $this->action,
                [$fields],
            );
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->removeUid();
            return false;
        }
    }
    
    final public function write(int $id, array $fields)
    {
        try{
            return $this->client->execute_kw(
                $this->db, $this->uid, $this->password,
                $this->module, $this->action,
                [[$id], $fields],
            );
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->removeUid();
            return false;
        }
    }
    
    final public function unlink(string $id, array $fields)
    {
        try{
            return $this->client->execute_kw(
                $this->db, $this->uid, $this->password,
                $this->module, $this->action,
                [[$id]],
            );
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->removeUid();
            return false;
        }
    }

    final public function removeImage(array $data): array
    {
        foreach($data as $key => $product)
        {
            unset($data[$key]['image_128']);
            unset($data[$key]['image_256']);
            unset($data[$key]['image_512']);
            unset($data[$key]['image_1024']);
            unset($data[$key]['image_1920']);
        }
        return $data;
    }
}
