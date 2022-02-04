<?php

declare(strict_types=1);

namespace Difra\Events;

use Difra\Debugger;

/**
 * Class Events
 * @package Difra
 */
class Event
{
    /** Init core */
    public const EVENT_CORE_INIT = 'core-init';
    /** Load configuration */
    public const EVENT_CONFIG_LOAD = 'config';
    /** For plugins system initialization */
    public const EVENT_PLUGIN_LOAD = 'plugins-load';
    /** For plugins' early hooks */
    public const EVENT_PLUGIN_INIT = 'plugins-init';
    /** For events before action processing */
    public const EVENT_ACTION_REDEFINE = 'pre-action';
    /** Search matching action event */
    public const EVENT_ACTION_SEARCH = 'action-find';
    /** Run controller->dispatch() */
    public const EVENT_ACTION_DISPATCH = 'action-dispatch';
    /** For events before action exec */
    public const EVENT_ACTION_PRE_RUN = 'init-done';
    /** Action exec */
    public const EVENT_ACTION_RUN = 'action-run';
    /** For events after action exec */
    public const EVENT_ACTION_ARRIVAL = 'action-arrival';
    /** For events run last */
    public const EVENT_ACTION_DONE = 'dispatch';
    /** Initialize render data */
    public const EVENT_RENDER_INIT = 'render-init';
    /** Run render */
    public const EVENT_RENDER_RUN = 'render-run';
    /** For events to be run after render */
    public const EVENT_RENDER_DONE = 'done';
    /** Run in all modes */
    public const RUN_ALL = 'all';
    /** Run in web mode */
    public const RUN_WEB = 'web';
    /** @var array */
    protected static array $systemEvents = [
        self::EVENT_CORE_INIT => self::RUN_ALL,
        self::EVENT_CONFIG_LOAD => self::RUN_ALL,
        self::EVENT_PLUGIN_LOAD => self::RUN_ALL,
        self::EVENT_PLUGIN_INIT => self::RUN_ALL,

        self::EVENT_ACTION_REDEFINE => self::RUN_WEB,
        self::EVENT_ACTION_SEARCH => self::RUN_WEB,
        self::EVENT_ACTION_DISPATCH => self::RUN_WEB,
        self::EVENT_ACTION_PRE_RUN => self::RUN_WEB,
        self::EVENT_ACTION_RUN => self::RUN_WEB,
        self::EVENT_ACTION_ARRIVAL => self::RUN_WEB,
        self::EVENT_ACTION_DONE => self::RUN_WEB,

        self::EVENT_RENDER_INIT => self::RUN_WEB,
        self::EVENT_RENDER_RUN => self::RUN_WEB,
        self::EVENT_RENDER_DONE => self::RUN_WEB
    ];
    // event object fields
    /** @var string|null Event name */
    private ?string $name;
    /** @var bool Event is running */
    private bool $running = false;
    /** @var bool Event completed */
    private bool $completed = false;
    /** @var callable[] Event handlers */
    private array $handlers = [];
    /** @var callable[] Default handlers */
    private array $defaultHandlers = [];
    /** @var bool preventDefault() called */
    private bool $preventDefault = false;
    /** @var bool stopPropagation() called */
    private bool $stopPropagation = false;

    /**
     * Factory
     * @param $name
     * @return \Difra\Events\System|\Difra\Events\Event
     * @throws \Difra\Events\Exception
     */
    public static function getInstance($name): Event|System
    {
        static $instances = [];
        if (isset($instances[$name])) {
            return $instances[$name];
        }
        if (isset(self::$systemEvents[$name])) {
            return $instances[$name] = new System($name);
        } else {
            return $instances[$name] = new Event($name);
        }
    }

    /**
     * Event constructor.
     * @param string $name
     */
    protected function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Trigger custom event
     * @throws Exception
     */
    public function trigger()
    {
        $this->start();
    }

    /**
     * Call handlers
     * @throws Exception
     */
    protected function start()
    {
        if ($this->running) {
            throw new Exception('An event is already running');
        }
        $this->running = true;
        if (!empty($this->handlers)) {
            $handlers = array_reverse($this->handlers);
            foreach ($handlers as $handler) {
                if ($this->stopPropagation) {
                    break;
                }
                Debugger::addEventLine(
                    is_string($handler)
                        ? "Handler for $this->name: $handler started"
                        : (
                    is_array($handler)
                        ? "Handler for $this->name: $handler[0]::$handler[1] started"
                        : "Handler for $this->name: [closure] started"
                    )
                );
                call_user_func($handler, $this);
            }
        }
        if (!empty($this->defaultHandlers)) {
            foreach ($this->defaultHandlers as $handler) {
                if ($this->preventDefault or $this->stopPropagation) {
                    break;
                }
                Debugger::addEventLine(
                    is_string($handler)
                        ? "Handler for $this->name: $handler started"
                        : (
                    is_array($handler)
                        ? "Handler for $this->name: $handler[0]::$handler[1] started"
                        : "Handler for $this->name: [closure] started"
                    )
                );
                call_user_func($handler, $this);
            }
        }
        $this->completed = true;
    }

    /**
     * Register handler
     * @param callable $handler
     */
    public function registerHandler(callable $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * Register default handler
     * @param callable $handler
     */
    public function registerDefaultHandler(callable $handler)
    {
        $this->realRegisterDefaultHandler($handler);
    }

    /**
     * Really register default handler
     * @param callable $handler
     */
    protected function realRegisterDefaultHandler(callable $handler)
    {
        $this->defaultHandlers[] = $handler;
    }

    /**
     * Stop event run
     */
    public function stopPropagation()
    {
        $this->stopPropagation = true;
    }

    /**
     * Prevent running default event handlers
     */
    public function preventDefault()
    {
        $this->preventDefault = true;
    }
}
