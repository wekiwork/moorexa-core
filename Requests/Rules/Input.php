<?php
namespace Lightroom\Requests\Rules;

use ReflectionClass;
use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
};
use Lightroom\Adapter\ClassManager;
use function Lightroom\Database\Functions\{db};
use Lightroom\Requests\Rules\Interfaces\InputInterface;
use Lightroom\Requests\Rules\Interfaces\ValidatorInterface;
/**
 * @package Input Rules 
 */
class Input implements InputInterface
{
    use InputRulesTrait;

    // identity created
    public $identityCreated = [];

    /**
     * @var ReflectionClass $validator 
     */
    public $validator;

    // on success request
    private $onSuccessBox = [];

    /**
     * @var object $classInstance
     */
    private $classInstance;

    /**
     * @var RuleModel $model
     */
    public $model;

    /**
     * @method InputInterface bindTo
     * @param mixed $classInstance
     * @param string $databaseType
     * @return InputInterface
     * 
     * @throws ClassNotFound
     */
    public function bindTo($classInstance, string $databaseType = '') : InputInterface 
    {
        // create class instance
        if (is_string($classInstance)) $classInstance = ClassManager::singleton($classInstance);

        // set class instance
        $this->classInstance = $classInstance;

        // load model
        $this->model = new RuleModel;

        // add table
        $this->model->table = basename(str_replace('\\', '/', get_class($classInstance)));

        // set database instance
        if ($databaseType !== '') :

            // @var Driver $database
            $database = $databaseType == 'default.database' ? db() : db_with($databaseType);

            // load database
            $this->model->setDatabase($database);

        endif;

        // set input instance
        $this->model->setInputInstance($this);

        return $this;
    }

    /** 
     * @method InputInterface getClassInstance
     * @return mixed
     */
    public function getClassInstance() 
    {
        return $this->classInstance;
    }

    /**
     * @method InputInterface setValidator
     * @param string $validatorClass
     * @return InputInterface
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     */
    public function setValidator(string $validatorClass) : InputInterface
    {
        // check if class exits
        if (!class_exists($validatorClass)) throw new ClassNotFound($validatorClass);

        // check if validator interface exists
        $reflection = new ReflectionClass($validatorClass);

        // throw interface not found exception
        if (!$reflection->implementsInterface(ValidatorInterface::class)) throw new InterfaceNotFound($validatorClass, ValidatorInterface::class);

        // set validator instance
        $this->validator = $reflection;

        // return instance
        return $this;
    }

    /**
     * @method InputInterface getValidator
     * @param array $data
     * @return ValidatorInterface
     * @throws ClassNotFound
     */
    public function getValidator(array $data) : ValidatorInterface
    {
        // @var ValidatorInterface $validator
        $validator = null;

        // check internal validator
        if ($this->validator !== null && get_class($this->validator) == ReflectionClass::class) :
            // create instance
            $validator = $this->validator->newInstanceWithoutConstructor();
        else:
            // load default validator class
            $validator = ClassManager::singleton(Validator::class);
        endif;  

        // load data into validator
        $validator->loadData($data);

        // return validator
        return $validator;
    }
}