<?php namespace Iyoworks\Support;

use Illuminate\Filesystem\Filesystem;

class Publisher {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The path where files should be published.
	 *
	 * @var string
	 */
	protected $publishPath;

	/**
	 * The path where packages are located.
	 *
	 * @var string
	 */
	protected $sourcePath;

	/**
	 * Determine if RuntimeException will be thrown on fail
	 *
	 * @var  boolean
	 */
	protected $quiteFail = false;

	/**
	 * Create a new asset publisher instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $publishPath
	 * @return void
	 */
	public function __construct(Filesystem $files, $publishPath)
	{
		$this->files = $files;
		$this->publishPath = $publishPath;
	}

	/**
	 * Copy all files from a given path to the publish path.
	 *
	 * @param  string  $name
	 * @param  string  $source
	 * @return bool
	 */
	public function publish($name, $source)
	{
		$destination = $this->getDestinationPath($name);
		
		$source = $source ?: $this->sourcePath;
		
		$success = $this->files->copyDirectory($source, $destination);

		if ( ! ($success || $this->quiteFail) )
			throw new \RuntimeException("Unable to publish files: $source => $destination.");

		return $success;
	}


	/**
	 * Remove all files from a given path.
	 *
	 * @param  string  $name
	 * @param  string  $directory
	 * @return bool
	 */
	public function unpublish($name, $directory, $levelsToBackTrack = 0)
	{
		$directory = $directory ?: $this->getDestinationPath($name);
		$success = $this->files->deleteDirectory($directory);

		if ($success && ($levelsToBackTrack > 0))
			$success = $this->deleteUpperLevels($directory, $levelsToBackTrack);

		if ( ! ($success || $this->quiteFail) )
			throw new \RuntimeException("Unable to remove files: $directory.");

		return $success;
	}

	protected function deleteUpperLevels($directory, $num)
	{
		$success = true;
		$i = 1;
		while ($success && $i < ($num+1) )
		{
			$charIdx = strrpos($directory, '/');
			$directory = substr($directory, 0, $charIdx);
			$files = glob($directory.'/*', GLOB_ONLYDIR);
			if(count($files) == 0) 
				$success = $this->files->deleteDirectory($directory);
			$i++;
		}
		return $success;
	}

	/**
	 * Set the default package path.
	 *
	 * @param  string  $sourcePath
	 * @return Sayla\Support\BasePublisher
	 */
	public function setSourcePath($sourcePath)
	{
		$this->sourcePath = $sourcePath;
		return $this;
	}

	/**
	 * Get the destination path
	 *
	 * @param  string  $appendage
	 * @return string
	 */
	public function getDestinationPath($appendage)
	{
		return $this->publishPath."/{$appendage}";
	}

	/**
	 * Return false if RuntimeException is thrown
	 * @param  boolean $value
	 * @return \Sayla\Support\Publisher
	 */
	public function failQuietly($value = true)
	{
		$this->quiteFail = $value;
		return $this;
	}

}