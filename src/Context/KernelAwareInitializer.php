<?php

namespace Laracasts\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Laracasts\Behat\ServiceContainer\LaravelBooter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class KernelAwareInitializer implements EventSubscriberInterface, ContextInitializer
{

    /**
     * The app kernel.
     *
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * The Behat context.
     *
     * @var Context
     */
    private $context;

    /**
     * Behat config.
     *
     * @var array
     */
    private $config;

    /**
     * Construct the initializer.
     *
     * @param HttpKernelInterface $kernel
     * @param array               $config
     */
    public function __construct(HttpKernelInterface $kernel, array $config)
    {
        $this->kernel = $kernel;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::AFTER => ['rebootKernel', -15]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        $this->context = $context;

        $this->setAppOnContext($this->kernel);
    }

    /**
     * Set the app kernel to the feature context.
     */
    private function setAppOnContext()
    {
        if ($this->context instanceof KernelAwareContext) {
            $this->context->setApp($this->kernel);
        }
    }

    /**
     * After each scenario, reboot the kernel.
     */
    public function rebootKernel()
    {
        $this->kernel->flush();

        $laravel = new LaravelBooter($this->kernel->basePath(), $this->kernel->environmentFile(), $this->config['bootstrap_path']);

        $this->context->getSession('laravel')->getDriver()->reboot($this->kernel = $laravel->boot());

        $this->setAppOnContext();
    }

}
