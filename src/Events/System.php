<?php

declare(strict_types=1);

namespace Difra\Events;

use Difra\Debugger;
use Difra\Envi;
use Difra\View\HttpError;

/**
 * Class System
 * @package Difra\Events
 */
class System extends Event
{
    /**
     * System constructor.
     * @param string $name
     * @throws Exception
     */
    protected function __construct(string $name)
    {
        self::init();
        if (!isset(self::$systemEvents[$name])) {
            throw new Exception('System event access to non-system event is not allowed');
        }
        parent::__construct($name);
    }

    /**
     * Trigger system event prevention
     * @throws Exception
     */
    public function trigger()
    {
        throw new Exception('System event should not be explicitly called');
    }

    /**
     * Register default handler
     * @param callable $handler
     * @throws Exception
     */
    public function registerDefaultHandler(callable $handler)
    {
        throw new Exception('System events default handlers are read only');
    }

    /**
     * Run system events
     * @throws \Difra\View\HttpError
     */
    public static function run()
    {
        try {
            self::init();
            foreach (self::$systemEvents as $eventType => $eventRun) {
                if ($eventRun === Event::RUN_WEB && Envi::getMode() !== Envi::MODE_WEB) {
                    continue;
                }
                self::getInstance($eventType)->start();
            }
        } catch (HttpError $e) {
            throw $e;
        } catch (\Exception $e) {
            if (Debugger::isEnabled()) {
                throw $e;
            }
            throw new HttpError(500);
        }
    }

    /**
     * Init system events
     * @throws \Difra\Events\Exception
     */
    private static function init()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        self::getInstance(Event::EVENT_CORE_INIT)->realRegisterDefaultHandler([\Difra\Debugger::class, 'init']);
        self::getInstance(Event::EVENT_CORE_INIT)->realRegisterDefaultHandler([\Difra\Envi\Setup::class, 'run']);
        self::getInstance(Event::EVENT_CORE_INIT)->realRegisterDefaultHandler([\Difra\Envi\Session::class, 'init']);
        self::getInstance(Event::EVENT_PLUGIN_LOAD)->realRegisterDefaultHandler([\Difra\Plugin::class, 'initAll']);

        self::getInstance(Event::EVENT_ACTION_SEARCH)->realRegisterDefaultHandler([\Difra\Controller::class, 'init']);
        self::getInstance(Event::EVENT_ACTION_DISPATCH)->realRegisterDefaultHandler([
            \Difra\Controller::class,
            'runDispatch'
        ]);
        self::getInstance(Event::EVENT_ACTION_RUN)->realRegisterDefaultHandler([\Difra\Controller::class, 'run']);
        self::getInstance(Event::EVENT_ACTION_ARRIVAL)->realRegisterDefaultHandler([
            \Difra\Controller::class,
            'runArrival'
        ]);
        self::getInstance(Event::EVENT_RENDER_RUN)->realRegisterDefaultHandler([\Difra\View\Output::class, 'start']);
    }
}
