<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use Lightroom\Packager\Moorexa\Router;
use Lightroom\Adapter\ClassManager;
use Lightroom\Packager\Moorexa\MVC\View;
use Lightroom\Packager\Moorexa\Helpers\{Assets, URL};
/**
 * @method View Packager
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait ViewPackage
{ 
    /**
     * @var array $package
     */
    private $package = [];

    /**
     * @method View loadPackage
     * @return View
     */
    public function loadPackage() : View
    {
        // @var array $config
        $config = Router::loadConfig();

        // set title
        $this->setTitle($config['title']);

        // set icon
        $this->setIcon($config['icon']);

        // set author
        $this->setAuthor($config['author']);

        // set description
        $this->setDescription($config['description']);

        // set keywords
        $this->setKeywords($config['keywords']);

        // set paths
        $this->setUrl(implode('/', URL::getIncomingUri()));

        // set the session_token
        $this->package['session_token'] = $this->session_token;

        return $this;
    }

    /**
     * @method View getSessionToken
     * @return string
     */
    public function getSessionToken() : string 
    {   
        return $this->__getter('session_token');
    }


    /**
     * @method View getTitle
     * @return string
     */
    public function getTitle() : string 
    {   
        return $this->__getter('title');
    }

    /**
     * @method View setTitle
     * @param string $title
     * @return void
     */
    public function setTitle(string $title) : void 
    {   
        $this->package['title'] = $title;
    }

    /**
     * @method View getUrl
     * @return string
     */
    public function getUrl() : string 
    {   
        return $this->__getter('url');
    }   

    /**
     * @method View setUrl
     * @param string $path
     * @return void
     */
    public function setUrl(string $path) : void 
    {   
        $this->package['url'] = $path;
    }

    /**
     * @method View getIcon
     * @return string
     */
    public function getIcon() : string 
    {   
        // load asset manager
        $asset = ClassManager::singleton(Assets::class);

        // load icon
        return $asset->image($this->__getter('icon'));
    }

    /**
     * @method View setIcon
     * @param string $file
     * @return void
     */
    public function setIcon(string $file) : void 
    {   
        $this->package['icon'] = $file;
    }

    /**
     * @method View getAuthor
     * @return string
     */
    public function getAuthor() : string 
    {   
        return $this->__getter('author');
    }

    /**
     * @method View setAuthor
     * @param string $author
     * @return void
     */
    public function setAuthor(string $author) : void 
    {   
        $this->package['author'] = $author;
    }

    /**
     * @method View getDescription
     * @return string
     */
    public function getDescription() : string 
    {   
        return $this->__getter('description');
    }

    /**
     * @method View setDescription
     * @param string $description
     * @return void
     */
    public function setDescription(string $description) : void 
    {   
        $this->package['description'] = $description;
    }   

    /**
     * @method View getKeywords
     * @return string
     */
    public function getKeywords() : string 
    {   
        return $this->__getter('keywords');
    }

    /**
     * @method View setKeywords
     * @param string $keywords
     * @return void
     */
    public function setKeywords(string $keywords) : void 
    {   
        $this->package['keywords'] = $keywords;
    }

    /**
     * @method View __getter
     * @param string $name
     * @return string
     */
    private function __getter(string $name) : string 
    {
        return isset($this->package[$name]) ? $this->package[$name] : '';
    }
}