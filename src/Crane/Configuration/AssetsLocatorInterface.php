<?php


namespace Crane\Configuration;

use Crane\Docker\Image\Image;

interface AssetsLocatorInterface
{

	/**
	 * @param Image  $image
	 * @param string $name
	 *
	 * @return string
	 */
	public function getAssetPath(Image $image, $name);
}