<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') or exit;

/**
 * ExtensionsController controller class.
 */
class ExtensionsController extends AbstractController
{
    use RequiresWooCommercePermissionsTrait;

    /** @var string state indicating that an extension is currently not installed */
    const EXTENSION_STATE_UNINSTALLED = 'uninstalled';

    /** @var string state indicating that an extension is currently installed */
    const EXTENSION_STATE_INSTALLED = 'installed';

    /** @var string state indicating that an extension is currently active */
    const EXTENSION_STATE_ACTIVE = 'activated';

    /** @var string state indicating that there is a more recent version of this plugin available */
    const EXTENSION_VERSION_STATE_STALE = 'stale';

    /** @var string state indicating that the installed version is the latest */
    const EXTENSION_VERSION_STATE_LATEST = 'latest';

    /** @var string route */
    protected $route = 'extensions';

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            'args' => [
                'query' => [
                    'required'          => false,
                    'type'              => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);

        register_rest_route(
            $this->namespace, "/{$this->route}/(?P<slug>[a-zA-Z0-9_-]+)", [
                [
                    'methods'             => 'PUT',
                    'callback'            => [$this, 'updateItem'],
                    'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                ],
                'args' => [
                    'slug' => [
                        'required'          => true,
                        'type'              => 'string',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Gets all the available managed extensions.
     *
     * @internal
     *
     * @param \WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     *
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request)
    {
        $extensions = $this->getManagedExtensions();

        $includeVersions = false;

        if (! empty($query = $request->get_param('query'))) {
            $query = json_decode($query, true);

            if ($filters = ArrayHelper::get($query, 'filters')) {
                $extensions = $this->filterExtensions($extensions, $filters);
            }

            if ($includes = ArrayHelper::get($query, 'includes')) {
                if (ArrayHelper::contains(ArrayHelper::wrap($includes), 'versions')) {
                    $includeVersions = true;
                }
            }
        }

        $items = array_map(function ($extension) use ($includeVersions) {
            return $this->prepareItem($extension, $includeVersions);
        }, $extensions);

        // @TODO: Should really by using shared library responses {JO 2021-02-15}
        return rest_ensure_response([
            'data' => $items,
            'count' => count($items),
        ]);
    }

    /**
     * Prepares the given extension object for API response.
     *
     * @param AbstractExtension $extension
     * @param bool $includeVersions
     * @return array
     */
    protected function prepareItem(AbstractExtension $extension, bool $includeVersions = false) : array
    {
        $installedVersion = null;
        if (! empty($version = $extension->getInstalledVersion())) {
            try {
                $installedVersion = $this->getManageExtensionVersion($extension, $version);
            } catch (Exception $exception) {
                // version not found
                $installedVersion = null;
            }
        }

        $extensionData = [
            'id' => $extension->getId(),
            'slug' => $extension->getSlug(),
            'name' => $extension->getName(),
            'shortDescription' => $extension->getShortDescription(),
            'type' => $extension->getType(),
            'category' => $this->getExtensionCategory($extension),
            'brand' => $extension->getBrand(),
            'installedVersion' => [
                'version' => $installedVersion ? $installedVersion->getVersion() : $extension->getInstalledVersion(),
                'minimumPhpVersion' => $installedVersion ? $installedVersion->getMinimumPHPVersion() : null,
                'minimumWordPressVersion' => $installedVersion ? $installedVersion->getMinimumWordPressVersion() : null,
                'minimumWooCommerceVersion' => $installedVersion ? $installedVersion->getMinimumWooCommerceVersion() : null,
                'releasedAt' => $installedVersion ? $installedVersion->getLastUpdated() : null,
                'package' => $installedVersion ? $installedVersion->getPackageUrl() : null,
                'state' => $this->getExtensionVersionState($extension, $installedVersion),
            ],
            'documentationUrl' => $extension->getDocumentationUrl(),
            'featured' => $this->isFeaturedExtension($extension),
            'state' => $extension->isActive() ? self::EXTENSION_STATE_ACTIVE : ($extension->isInstalled() ? self::EXTENSION_STATE_INSTALLED : self::EXTENSION_STATE_UNINSTALLED),
        ];

        if ($includeVersions) {
            $extensionData['versions'] = [];

            foreach (ManagedExtensionsRepository::getManagedExtensionVersions($extension) as $version) {
                $extensionData['versions'][] = [
                    'version' => $version->getVersion(),
                    'minimumPhpVersion' => $version->getMinimumPHPVersion(),
                    'minimumWordPressVersion' => $version->getMinimumWordPressVersion(),
                    'minimumWooCommerceVersion' => $version->getMinimumWooCommerceVersion(),
                    'releasedAt' => $version->getLastUpdated(),
                    'package' => $version->getPackageUrl(),
                ];
            }
        }

        return $extensionData;
    }

    /**
     * Gets the category for the given exception.
     *
     * Returns the value set in the extension object or attempts to find a category in the configuration.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension the extension object
     *
     * @return string|null
     */
    private function getExtensionCategory(AbstractExtension $extension)
    {
        if ($category = $extension->getCategory()) {
            return $category;
        }

        return ArrayHelper::get(Configuration::get('mwc_extensions.categories', []), $extension->getSlug());
    }

    /**
     * Gets the state for the version associated with the given extension.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension the extension object
     * @param AbstractExtension|null $installedVersion optional extension object representing the currently installed version of the extension
     *
     * @return string|null
     */
    private function getExtensionVersionState(AbstractExtension $extension, AbstractExtension $installedVersion = null)
    {
        if (! $extension->isInstalled()) {
            return null;
        }

        $installedVersion = $installedVersion instanceof AbstractExtension ? $installedVersion->getVersion() : $extension->getInstalledVersion();

        if (version_compare($extension->getVersion(), $installedVersion, '>')) {
            return self::EXTENSION_VERSION_STATE_STALE;
        }

        return self::EXTENSION_VERSION_STATE_LATEST;
    }

    /**
     * Gets a list of the extension slugs that should be excluded, based on site conditions defined in the configuration.
     *
     * @return array
     * @throws Exception
     */
    protected function getExcludedExtensions(): array
    {
        $excludedSlugs = [];
        $exclusionRules = Configuration::get('mwc_extensions.excluded');

        if (empty($exclusionRules)) {
            return $excludedSlugs;
        }

        foreach ($exclusionRules as $slug => $rules) {
            if (false === $rules) {
                // should not be excluded
                continue;
            } elseif (true === $rules) {
                // should be excluded
                $excludedSlugs[] = $slug;
                continue;
            }

            // only evaluate rules with conditions
            if (! is_array($rules) || empty($rules)) {
                continue;
            }

            $countries = ArrayHelper::get($rules, 'countries');
            $currencies = ArrayHelper::get($rules, 'currencies');
            $plans = ArrayHelper::get($rules, 'plans');
            $reseller = ArrayHelper::get($rules, 'reseller');

            if ($countries && ! ArrayHelper::contains($countries, WooCommerceRepository::getBaseCountry())) {
                continue;
            }

            if ($currencies && ! ArrayHelper::contains($currencies, WooCommerceRepository::getCurrency())) {
                continue;
            }

            if ($plans && ! ArrayHelper::contains($plans, ManagedWooCommerceRepository::getManagedWordPressPlan())) {
                continue;
            }

            if (null !== $reseller && $reseller !== ManagedWooCommerceRepository::isReseller()) {
                continue;
            }

            $excludedSlugs[] = $slug;
        }

        return $excludedSlugs;
    }

    /**
     * Determines whether the given extension is featured.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     *
     * @return bool
     */
    private function isFeaturedExtension(AbstractExtension $extension) : bool
    {
        return ArrayHelper::has(Configuration::get('mwc_extensions.featured'), $extension->getSlug());
    }

    /**
     * Filters the managed extensions.
     *
     * @internal
     *
     * @param \GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension[] $extensions
     * @param array $filters
     * @return \GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension[]
     */
    private function filterExtensions(array $extensions, array $filters) : array
    {
        foreach ($filters as $property => $filter) {
            $extensions = ArrayHelper::where($extensions, function (AbstractExtension $extension) use ($filter, $property) {
                if (! $filterValue = ArrayHelper::get($filter, 'eq')) {
                    return false;
                }

                if ('featured' === $property) {
                    return $filterValue === $this->isFeaturedExtension($extension);
                }

                $methodName = 'get'.ucfirst($property);

                return method_exists($extension, $methodName) && $filterValue === $extension->$methodName();
            }, false);
        }

        return $extensions;
    }

    /**
     * Updates the specified managed extension.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function updateItem($request)
    {
        $payload = $request->get_json_params();

        try {
            $extension = $this->getManagedExtension(StringHelper::sanitize($request->get_param('slug')));

            if ($this->shouldUninstallExtension($extension, $payload)) {
                $this->uninstallExtension($extension);
            } elseif ($this->shouldDeactivateExtension($extension, $payload)) {
                $this->deactivateExtension($extension);
            } elseif ($this->shouldSwitchExtensionVersion($extension, $payload)) {
                $extension = $this->switchExtensionVersion($extension, $payload);
            } elseif ($this->shouldInstallExtension($extension, $payload)) {
                $extension = $this->installExtension($extension, $payload);
            } elseif ($this->shouldActivateExtension($extension, $payload)) {
                $this->activateExtension($extension, $payload);
            }
        } catch (Exception $exception) {
            return new WP_Error('error_updating_extension', $exception->getMessage());
        }

        return rest_ensure_response($this->prepareItem($extension));
    }

    /**
     * Gets the extension identified with the given slug.
     *
     * @since 1.0.0
     *
     * @param string $slug extension slug
     *
     * @return AbstractExtension
     *
     * @throws Exception
     */
    private function getManagedExtension(string $slug) : AbstractExtension
    {
        $extensions = ArrayHelper::where($this->getManagedExtensions(), static function (AbstractExtension $extension) use ($slug) {
            return $extension->getSlug() === $slug;
        });

        if (! $extensions) {
            throw new Exception(sprintf(__('Could not find an extension with the given slug: %s.', 'mwc-dashboard'), $slug));
        }

        return current($extensions);
    }

    /**
     * Gets the managed extensions, filtering out any excluded extensions.
     *
     * @since x.y.z
     *
     * @return AbstractExtension[]
     */
    private function getManagedExtensions() : array
    {
        $excludedExtensions = $this->getExcludedExtensions();

        return ArrayHelper::where(
            ManagedExtensionsRepository::getManagedExtensions(),
            function ($extension) use ($excludedExtensions) {
                return ! ArrayHelper::contains($excludedExtensions, $extension->getSlug());
            },
            false
        );
    }

    /**
     * Determines whether the given extension should be uninstalled based on the request payload.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function shouldUninstallExtension(AbstractExtension $extension, array $payload) : bool
    {
        return $extension->isInstalled() && ArrayHelper::get($payload, 'state') === self::EXTENSION_STATE_UNINSTALLED;
    }

    /**
     * Determines whether the given extension should be deactivated based on the request payload.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param array $payload request payload
     *
     * @return bool
     */
    private function shouldDeactivateExtension(AbstractExtension $extension, array $payload) : bool
    {
        return $extension->isActive() && ArrayHelper::get($payload, 'state') === self::EXTENSION_STATE_INSTALLED;
    }

    /**
     * Determines whether we should switch the version for the given extension based on the request payload.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param array $payload request payload
     *
     * @return bool
     */
    private function shouldSwitchExtensionVersion(AbstractExtension $extension, array $payload) : bool
    {
        if (! $version = ArrayHelper::get($payload, 'version.version')) {
            return false;
        }

        if (! method_exists($extension, 'getInstalledVersion')) {
            return false;
        }

        return $extension->isInstalled() && version_compare($extension->getInstalledVersion(), $version, '!=');
    }

    /**
     * Determines whether we should install the given extension based on the request payload.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param array $payload request payload
     *
     * @return bool
     */
    private function shouldInstallExtension(AbstractExtension $extension, array $payload) : bool
    {
        if ($extension->isInstalled()) {
            return false;
        }

        return ArrayHelper::get($payload, 'state') === self::EXTENSION_STATE_INSTALLED;
    }

    /**
     * Determines whether we should activate the given extension based on the request payload.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param array $payload request payload
     *
     * @return bool
     */
    private function shouldActivateExtension(AbstractExtension $extension, array $payload) : bool
    {
        if ($extension->isActive()) {
            return false;
        }

        return ArrayHelper::get($payload, 'state') === self::EXTENSION_STATE_ACTIVE;
    }

    /**
     * Uninstalls the given extension.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     */
    private function uninstallExtension(AbstractExtension $extension)
    {
        $extension->uninstall();
    }

    /**
     * Deactivates the given extension.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     */
    private function deactivateExtension(AbstractExtension $extension)
    {
        $extension->deactivate();
    }

    /**
     * Installs the specified version of the given extension.
     *
     * Returns the installed extension.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param array $payload request payload
     *
     * @return AbstractExtension
     *
     * @throws Exception
     */
    private function switchExtensionVersion(AbstractExtension $extension, array $payload) : AbstractExtension
    {
        if ($shouldActivate = $extension->isActive()) {
            $extension->deactivate();
        }

        $extension = $this->installExtension($extension, $payload);

        if ($shouldActivate) {
            $extension->activate();
        }

        return $extension;
    }

    /**
     * Installs the given extension optionally switching to the version specified in the request payload.
     *
     * Returns the installed extension.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param array $payload request payload
     *
     * @return AbstractExtension
     *
     * @throws Exception
     */
    private function installExtension(AbstractExtension $extension, array $payload) : AbstractExtension
    {
        $version = ArrayHelper::get($payload, 'version.version');

        if (version_compare($version, $extension->getInstalledVersion(), '!=')) {
            $extension = $this->getManageExtensionVersion($extension, $version);
        }

        $extension->install();

        return $extension;
    }

    /**
     * Gets a specific version of the given extension.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param string $version
     *
     * @return AbstractExtension
     *
     * @throws Exception
     */
    private function getManageExtensionVersion(AbstractExtension $extension, string $version) : AbstractExtension
    {
        $versions = ArrayHelper::where(ManagedExtensionsRepository::getManagedExtensionVersions($extension), function (AbstractExtension $element) use ($version) {
            return version_compare($element->getVersion(), $version, '=');
        });

        if (! $versions) {
            throw new Exception(sprintf(__('Could not find version %1$s of %2$s.', 'mwc-dashboard'), $version, $extension->getName()));
        }

        return current($versions);
    }

    /**
     * Activates the given extension.
     *
     * @since 1.0.0
     *
     * @param AbstractExtension $extension extension object
     * @param array $payload request payload
     *
     * @throws Exception
     */
    private function activateExtension(AbstractExtension $extension, array $payload)
    {
        if (! $extension->isInstalled()) {
            $extension = $this->installExtension($extension, $payload);
        }

        $extension->activate();
    }

    /**
     * Checks if the current user can update items through the controller.
     *
     * @return bool|\WP_Error
     */
    public function getItemsPermissionsCheck()
    {
        return current_user_can('install_plugins') && current_user_can('activate_plugins');
    }

    /**
     * Checks if the current user can update items through the controller.
     *
     * @return bool|\WP_Error
     */
    public function updateItemPermissionsCheck()
    {
        return current_user_can('install_plugins') && current_user_can('activate_plugins');
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'extension',
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'description' => __('The extension ID.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'slug' => [
                    'description' => __('The extension slug.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'name' => [
                    'description' => __('The extension name.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'shortDescription' => [
                    'description' => __('The extension short description.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'type' => [
                    'description' => __('The extension type.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'enum'        => ['plugin', 'theme'],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'category' => [
                    'description' => __('The extension category.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'enum'        => [
                        'Cart and Checkout',
                        'Marketing and Messaging',
                        'Merchandising',
                        'Payments',
                        'Product Type',
                        'Shipping',
                        'Store Management',
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'brand' => [
                    'description' => __('The extension brand.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'enum'        => ['godaddy', 'woo'],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'installedVersion' => [
                    'description' => __('Information about the extension installed version.', 'mwc-dashboard'),
                    'type'        => 'object',
                    'properties'  => [
                        'version' => [
                            'description' => __('The version number.', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'minimumPhpVersion' => [
                            'description' => __('The required PHP version.', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'minimumWordPressVersion' => [
                            'description' => __('The required WordPress version.', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'minimumWooCommerceVersion' => [
                            'description' => __('The required WooCommerce version.', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'releasedAt' => [
                            'description' => __('The timestamp in seconds when the version was released.', 'mwc-dashboard'),
                            'type'        => 'int',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'package' => [
                            'description' => __('The URL from where the package can be downloaded', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'state' => [
                            'description' => __('The state of the installed version (whether or not it is the latest version)', 'mwc-dashboard'),
                            'type'        => 'string',
                            'enum'        => [self::EXTENSION_VERSION_STATE_LATEST, self::EXTENSION_VERSION_STATE_STALE],
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'documentationUrl' => [
                    'description' => __('The extension documentation URL.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'featured' => [
                    'description' => __('Whether or not the extension is featured.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'state' => [
                    'description' => __('The extension state.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'enum'        => [self::EXTENSION_STATE_ACTIVE, self::EXTENSION_STATE_INSTALLED, self::EXTENSION_STATE_UNINSTALLED],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'versions' => [
                    'description' => __('Information about the versions available for the extension.', 'mwc-dashboard'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'version' => [
                                'description' => __('The version number.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'minimumPhpVersion' => [
                                'description' => __('The required PHP version.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'minimumWordPressVersion' => [
                                'description' => __('The required WordPress version.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'minimumWooCommerceVersion' => [
                                'description' => __('The required WooCommerce version.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'releasedAt' => [
                                'description' => __('The timestamp in seconds when the version was released.', 'mwc-dashboard'),
                                'type'        => 'int',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'package' => [
                                'description' => __('The URL from where the package can be downloaded', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
            ],
        ];
    }
}
