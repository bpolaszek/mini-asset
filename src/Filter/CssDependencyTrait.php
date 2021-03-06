<?php
/**
 * MiniAsset
 * Copyright (c) Mark Story (http://mark-story.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Mark Story (http://mark-story.com)
 * @since         0.0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace MiniAsset\Filter;

use MiniAsset\AssetTarget;
use MiniAsset\File\Local;
use MiniAsset\Utility\CssUtils;

trait CssDependencyTrait
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(AssetTarget $target)
    {
        $children = [];
        foreach ($target->files() as $file) {
            $imports = CssUtils::extractImports($file->contents());
            if (empty($imports)) {
                continue;
            }

            $ext = $this->_settings['ext'];
            $extLength = strlen($ext);

            $deps = [];
            foreach ($imports as $name) {
                if ('.css' === substr($name, -4)) {
                    // skip normal css imports
                    continue;
                }
                if ($ext !== substr($name, -$extLength)) {
                    $name .= $ext;
                }
                $deps[] = $name;
            }
            foreach ($deps as $import) {
                $path = $this->_findFile($import);
                try {
                    $file = new Local($path);
                    $newTarget = new AssetTarget('phony.css', [$file]);
                    $children[] = $file;
                } catch (\Exception $e) {
                    // Do nothing, we just skip missing files.
                    // sometimes these are things like compass or bourbon
                    // internals.
                    $newTarget = false;
                }

                // Only recurse through non-css imports as css files are not
                // inlined by less/sass.
                if ($newTarget && $ext === substr($import, -$extLength)) {
                    $children = array_merge($children, $this->getDependencies($newTarget));
                }
            }
        }
        return $children;
    }

    /**
     * Attempt to locate a file in the configured paths.
     *
     * @param string $file The file to find.
     * @return string The resolved file.
     */
    protected function _findFile($file)
    {
        foreach ($this->_settings['paths'] as $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (file_exists($path . $file)) {
                return $path . $file;
            }
        }
        return $file;
    }
}
