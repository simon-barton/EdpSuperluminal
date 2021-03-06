<?php

namespace EdpSuperluminal;

use EdpSuperluminal\ShouldCacheClass\ShouldCacheClassSpecification;
use Zend\Code\Reflection\ClassReflection;
use Zend\Code\Scanner\FileScanner;

class CacheBuilder
{
    protected $knownClasses = array();

    /**
     * @var CacheCodeGenerator
     */
    protected $cacheCodeGenerator;

    /**
     * @var ShouldCacheClassSpecification
     */
    protected $shouldCacheClass;

    /**
     * @param CacheCodeGenerator $cacheCodeGenerator
     * @param ShouldCacheClassSpecification $shouldCacheClass
     */
    public function __construct(CacheCodeGenerator $cacheCodeGenerator, ShouldCacheClassSpecification $shouldCacheClass)
    {
        $this->cacheCodeGenerator = $cacheCodeGenerator;
        $this->shouldCacheClass = $shouldCacheClass;
    }

    /**
     * Cache declared interfaces and classes to a single file
     * @todo - extract the file_put_contents / php_strip_whitespace calls or figure out a way to mock the filesystem
     *
     * @param string
     * @return void
     */
    public function cache($classCacheFilename)
    {
        if (file_exists($classCacheFilename)) {
            $this->reflectClassCache($classCacheFilename);
            $code = file_get_contents($classCacheFilename);
        } else {
            $code = "<?php\n";
        }

        $classes = array_merge(get_declared_interfaces(), get_declared_classes());

        foreach ($classes as $class) {
            $class = new ClassReflection($class);

            if (!$this->shouldCacheClass->isSatisfiedBy($class)) {
                continue;
            }

            // Skip any classes we already know about
            if (in_array($class->getName(), $this->knownClasses)) {
                continue;
            }

            $this->knownClasses[] = $class->getName();

            $code .= $this->cacheCodeGenerator->getCacheCode($class);
        }

        file_put_contents($classCacheFilename, $code);

        // minify the file
        file_put_contents($classCacheFilename, php_strip_whitespace($classCacheFilename));
    }

    /**
     * Determine what classes are present in the cache
     *
     * @param $classCacheFilename
     * @return void
     */
    protected function reflectClassCache($classCacheFilename)
    {
        $scanner = new FileScanner($classCacheFilename);
        $this->knownClasses = array_unique($scanner->getClassNames());
    }
}