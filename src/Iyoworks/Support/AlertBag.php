<?php namespace Iyoworks\Support;

use BadMethodCallException;
use BadFunctionCallException;
use Illuminate\Support\MessageBag;
use Illuminate\Session\Store;
use Illuminate\Translation\Translator;

/**
 * Modified version of Alerts for Laravel By Simon Hampel.
 * @website https://bitbucket.org/hampel/alerts
 */
class AlertBag extends MessageBag {
	/**
	 * Translate messages to different languages
	 * @var Illuminate\Translation\Translator
	 */
	protected static $translator;

	/**
	 * Flash the message bag to the session
	 * @var Illuminate\Session\Store
	 */
	protected static $session;

	/**
	 * The key to use when flashing the message bag
	 * @var string
	 */
	protected $sessionKey = 'alerts';

	/**
	 * All levels and there corresponding view names
	 * @var array
	 */
	protected $levels = array(
		'info' => 'info',
		'warning' => 'warning',
		'error' => 'danger',
		'success' => 'success'
		);

	/**
	 * Create a new message bag instance.
	 *
	 * @param  array  $messages
	 * @return void
	 */
	public function __construct(array $messages = array(), array $levels = null)
	{
		parent::__construct($messages);
		if ($levels) $this->levels = $levels;
	}

	/**
	 * Store the messages in the current session.
	 */
	public function flash()
	{
		if(static::$session)
			static::$session->flash($this->getSessionKey(), $this);
		return $this;
	}

	public function getLevelNames()
	{
		return array_keys($this->levels);
	}

	/**
	 * Returns the session key from the config.
	 *
	 * @return string
	 */
	public function getSessionKey()
	{
		return $this->sessionKey;
	}

	/**
	 * Returns the session key from the config.
	 *
	 * @return string
	 */
	public function setSessionKey($key)
	{
		$this->sessionKey = $key;
		return $this;
	}

	public function getMessagesByType($searchType = null)
	{
		$alerts = [];
		foreach ($this->getMessages() as $type => $messages)
		{
			if ($searchType && $type !== $searchType) 
				continue;
			if (!in_array($type, $this->getLevelNames())) 
				continue;
			foreach ($messages as $message)
			{
				$_lvl = $this->levels[$type];
				$alerts[$_lvl][] = $message;
			}
		}

		return $alerts;
	}

	protected function translateMessage($message, $replacements)
	{
		if (static::$translator and static::$translator->has($message))
		{
			// if there is a language entry which matches this message, use that instead

			if (isset($replacements) AND is_array($replacements))
			{
				// there are replacements specified
				$message = static::$translator->get($message, $replacements);
			}
			else
			{
				// no replacement, just a plain language entry
				$message = static::$translator->get($message);
			}
		}

		return $message;
	}

	/**
	 * Merge a message bag
	 * @param  Illuminate\Support\MessageBag $bag 
	 * @return Iyoworks\Support\AlertBag       
	 */
	public function mergeBag(MessageBag $bag)
	{
		$this->merge($bag->getMessages());
		
		return $this;
	}

	/**
	 * Dynamically handle alert additions.
	 *
	 * @param  string  $method
	 * @param  array   $args
	 * @return mixed
	 * @throws BadMethodCallException
	 * @throws BadFunctionCallException
	 */
	public function __call($method, $args)
	{
		// Check if the method is in the allowed alert levels array.
		if (in_array($method, $this->getLevelNames()))
		{
			if (isset($args[0]))
			{
				$messages = $args[0];
				if (!is_array($messages))
				{
					$messages = array($messages);
				}

				foreach ($messages as $message)
				{
					$message = $this->translateMessage($message, isset($args[1]) ? $args[1] : null);

					$this->add($method, $message);
				}

				return $this;
			}

			throw new BadFunctionCallException("Missing parameter to method {$method}");

		}
		throw new BadMethodCallException("Method {$method} does not exist.");
	}


	/**
	 * [setTranslator description]
	 * @param Illuminate\Translation\Translator $translator
	 */
	public static function setTranslator(Translator $translator)
	{
		static::$translator = $translator;
	}

	/**
	 * [setSessionStore description]
	 * @param Illuminate\Session\Store $session
	 */
	public static function setSessionStore(Store $session)
	{
		static::$session = $session;
	}

}