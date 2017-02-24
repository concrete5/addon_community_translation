<?php
namespace CommunityTranslation\Controller;

use CommunityTranslation\Service\Access;
use Concrete\Core\Block\BlockController as CoreBlockController;
use Exception;

abstract class BlockController extends CoreBlockController
{
    /**
     * @var Access|null
     */
    private $access = null;

    /**
     * @return Access
     */
    protected function getAccess()
    {
        if ($this->access === null) {
            $this->access = $this->app->make(Access::class);
        }

        return $this->access;
    }


    /**
     * {@inheritdoc}
     *
     * @see CoreBlockController::isValidControllerTask()
     */
    public function isValidControllerTask($method, $parameters = [])
    {
        $result = false;
        if (parent::isValidControllerTask($method, $parameters)) {
            $bID = array_pop($parameters);
            if ((is_string($bID) && is_numeric($bID)) || is_int($bID)) {
                if ($this->bID == $bID) {
                    $result = true;
                }
            }
        }
    
        return $result;
    }

    /**
     * @param array $args
     *
     * @return \Concrete\Core\Error\Error|\Concrete\Core\Error\ErrorList\ErrorList|array
     */
    abstract protected function normalizeArgs(array $args);

    /**
     * {@inheritdoc}
     *
     * @see CoreBlockController::validate()
     */
    public function validate($args)
    {
        $check = $this->normalizeArgs(is_array($args) ? $args : []);

        return is_array($check) ? true : $check;
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreBlockController::save()
     */
    public function save($args)
    {
        $normalized = $this->normalizeArgs(is_array($args) ? $args : []);
        if (!is_array($normalized)) {
            throw new Exception(implode("\n", $normalized->getList()));
        }
        parent::save($normalized);
    }
}