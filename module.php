<?php

declare(strict_types=1);

namespace JustCarmen\Webtrees\Module\SimpleMenu;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Aura\Router\RouterContainer;
use Fisharebest\Webtrees\FlashMessages;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleMenuTrait;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Http\RequestHandlers\ModulesMenusAction;

/**
 * Anonymous class - provide a custom menu option and page
 */
return new class extends AbstractModule implements ModuleCustomInterface, ModuleMenuInterface, ModuleConfigInterface, RequestHandlerInterface
{
    use ModuleCustomTrait;
    use ModuleMenuTrait;
    use ModuleConfigTrait;

    protected const ROUTE_URL   = '/tree/{tree}/{module}/{menu}/{page}';

     /**
     * @var string
     */
    public const CUSTOM_AUTHOR = 'JustCarmen';

    /**
     * @var string
     */
    public const CUSTOM_VERSION = '1.1';
     /**
     * @var string
     */
    public const GITHUB_REPO = 'webtrees-simple-menu';

     /**
     * @var string
     */
    public const AUTHOR_WEBSITE = 'https://justcarmen.nl';

    /**
     * How should this module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string
    {

        /* I18N: Name of a module */
        return I18N::translate('Simple Menu ') . (int)substr($this->name(), -2, 1) . ' - ' . $this->getPreference('menu-title');
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function description(): string
    {
        /* I18N: Description of the “Simple Menu” module */
        return I18N::translate('Easily add an extra mainmenu item and page to your webtrees website.');
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * A URL that will provide the latest stable version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        return 'https://raw.githubusercontent.com/' . self::CUSTOM_AUTHOR . '/' . self::GITHUB_REPO . '/main/latest-version.txt';
    }

    /**
     * Fetch the latest version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersion(): string
    {
        return 'https://github.com/' . self::CUSTOM_AUTHOR . '/' . self::GITHUB_REPO . '/releases/latest';
    }

     /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return self::AUTHOR_WEBSITE;
    }

    /**
     * Bootstrap the module
     */
    public function boot(): void
    {
        $router_container = app(RouterContainer::class);
        assert($router_container instanceof RouterContainer);

        $router_container->getMap()
            ->get(static::class, static::ROUTE_URL, $this);

        // Register a namespace for our views.
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
    }

     /**
     * Where does this module store its resources
     *
     * @return string
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

     /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        return $this->viewResponse($this->name() . '::edit', [
            'title'      => $this->title(),
            'menu_title' => $this->getPreference('menu-title'),
            'page_title' => $this->getPreference('page-title'),
            'page_body'  => $this->getPreference('page-body'),
        ]);
    }

    /**
     * Save the user preference.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();

        $this->setPreference('menu-title', $params['menu-title']);
        $this->setPreference('page-title', $params['page-title']);
        $this->setPreference('page-body', $params['page-body']);

        $message = I18N::translate('The preferences for the module “%s” have been updated.', $this->title());
        FlashMessages::addMessage($message, 'success');

        return redirect(route(ModulesMenusAction::class));
    }

     /**
     * The default position for this menu.  It can be changed in the control panel.
     *
     * @return int
     */
    public function defaultMenuOrder(): int
    {
        return 99;
    }

    /**
     * A menu, to be added to the main application menu.
     *
     * @param Tree $tree
     *
     * @return Menu|null
     */
    public function getMenu(Tree $tree): ?Menu
    {
        if ($tree === null) {
            return '';
        }

        $page_title = $this->getPreference('page-title');
        $menu_title = $this->getPreference('menu-title');

        $url = route(static::class, [
            'tree'   => $tree->name(),
            'module' => $this->name(),
            'menu'   => $this->getslug($menu_title),
            'page'   => $this->getslug($page_title)
        ]);

        return new Menu($menu_title, e($url), 'jc-simple-menu-' . e(strtolower($menu_title)));
    }

     /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $page_title = $this->getPreference('page-title');
        $page_body  = $this->getPreference('page-body');

        return $this->viewResponse($this->name() . '::page', [
            'tree'          => $tree,
            'title'         => $this->title(),
            'module'        => $this->name(),
            'is_admin'      => Auth::isAdmin(),
            'page_title'    => $page_title,
            'page_body'     => $page_body
        ]);
    }

     /**
     * Get the url slug for this page
     */
    public function getSlug($string): String
    {
        return preg_replace('/\s+/', '-', strtolower(preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities($string))));
    }
};
