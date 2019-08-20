<?php
// phpcs:ignore
namespace Phlesk;

// phpcs:ignore
class Utils
{
    /**
        Return the default permissions for an extension.

        This routine will also initialize the default value if not already present.

        @return Bool
     */
    public static function defaultPermission()
    {
        if (!self::_canManagePlans()) {
            // If we can't manage plans, the permission is always true.
            \pm_Settings::set('permission-default', 1);
            return true;
        } else {
            $permissionConfig = \pm_Settings::get('permission-default', null);

            // Initialize the default if necessary
            if ($permissionConfig === null) {
                // If we have no pervious domains then we enable the permission by default
                $domains = \pm_Domain::getAllDomains(true);

                if (count($domains) == 0) {
                    $permissionConfig = 1;
                } else {
                    $permissionConfig = 0;
                }
                \pm_Settings::set('permission-default', $permissionConfig);
            }

            return (bool)$permissionConfig;
        }
    }


    /**
       Download the file into $target_directory

       This routine ensures the file appears atomically,
       by downloading to a temporary file in $target_directory and renaming at the end.

       @param String                $url              The url to download.
       @param String                $target_directory The target directory to download to.
       @param String                $file_name        The target file name.
       @param \pm_ServerFileManager $fm               A file manager, if any

       @return true if the tarball is available, false if not
     */
    public static function downloadFile($url, $target_directory, $file_name, \pm_ServerFileManager $fm)
    {
        $target_dir = rtrim($target_directory, '/');

        $tar_file = "{$target_dir}/{$file_name}";
        $tmp_file = tempnam($target_dir, $file_name);

        if ($fm->fileExists($tar_file)) {
            return true;
        }

        \pm_Log::debug("Downloading {$tar_file}");

        // Download to temp directory and then move, for it to appear atomic
        $result = self::exec(["wget", "-O{$tmp_file}", "{$url}"], true);

        // This could also fail because there is no connection to the internet, so not necessarily an error.
        if ($result['code'] != 0) {
            \pm_Log::info(
                "Failed to download {$file_name}: '" . $result['stderr']
            );

            return false;
        }

        // We check again in case the file has been downloaded by someone else meanwhile
        if (!$fm->fileExists($tar_file)) {
            $result = self::exec(["mv", "{$tmp_file}", "{$tar_file}"]);
            if ($result['code'] != 0) {
                return false;
            }
        }

        return true;
    }

    /**
        Download the extension's application release file from the interwebz.

        @return Bool
     */
    public static function downloadRelease()
    {
        self::_waitForCompleteInstallation();

        $baseUrl = "https://mirror.kolabenterprise.com/pub/releases/";

        $module = \Phlesk\Context::getModuleId();
        $extension = ucfirst(strtolower($module));

        $versionClass = "Modules_{$extension}_Version";

        if (!class_exists($versionClass)) {
            \pm_Log::err("No version class exists for {$extension}");
            return false;
        }

        $filename = $versionClass::getFilename();

        $url = $baseUrl . $filename;

        $fm = new \pm_ServerFileManager();
        return self::downloadFile($url, $varDir, $filename, $fm);
    }

    /**
        Render the contents of a file from a template.

        @param String                $tpl    Template file to use, obtained from extension's var
                                             directory.
        @param Array                 $substs Substitution values.
        @param \pm_ServerFileManager $fm     A file manager, if any

        @return String
     */
    public static function renderTemplate($tpl, Array $substs, $fm = null)
    {
        $varDir = rtrim(\pm_Context::getVarDir(), '/');

        if (!$fm) {
            $fm = new \pm_ServerFileManager();
        }

        if (!$fm->fileExists($varDir . '/' . $tpl)) {
            return "";
        }

        return str_replace(
            array_keys($substs),
            array_values($substs),
            $fm->fileGetContents($varDir . '/' . $tpl)
        );
    }

    /**
        Determine whether or not this instance of Plesk is able to manage
        hosting plans and reseller plans.

        Without either of those, any permission could no longer be toggled.

        @return Bool
     */
    private static function _canManagePlans()
    {
        $properties = (new \pm_License())->getProperties();
        return ($properties['can-manage-customers'] && $properties['can-manage-resellers']);
    }

    /**
        Wait for any post-install jobs to have actually completed.

        @return NULL
     */
    private static function _waitForCompleteInstallation()
    {
        $post_installing = \pm_Settings::get('installing', null) == "true";

        // Useless. Join the query below.
        /*
        $module = new \pm_Extension("seafile");
        $module_id = $module->getId();
        */

        $db = \pm_Bootstrap::getDbAdapter();

        while ($post_installing) {
            \pm_Log::debug("Seafile extension is not yet completely installed ...");
            sleep(3);

            /**
                TODO:

                Raise with plesk the fact that a \pm_Settings::get('installing') won't update
                itself even though it can be shown the underlying data changes.

                // Doesn't work:

                $post_installing = \pm_Settings::get('installing') == "true";

                // Doesn't work:

                \pm_Context::reset();
                \pm_Context::init("seafile");

                $post_installing = \pm_Settings::get('installing') == "true";

                // Doesn't work either:

                $settings = new \pm_Settings();
                $post_installing = $settings->get('installing') == "true";
                unset $settings;

                // Verify the underlying data with (see output in logs):

                \Phlesk::exec(
                    [
                        'plesk',
                        'db',
                        '-e',
                        'SELECT value FROM ModuleSettings WHERE name = "installing";'
                    ]
                );
            */

            $result = $db->query(
                sprintf(
                    "SELECT ms.value FROM ModuleSettings ms
                        INNER JOIN Modules m ON m.id = ms.module_id
                        WHERE m.name = '%s' AND
                            ms.name = 'installing' AND
                            ms.value = 'false'",
                    \Phlesk\Context::getModuleId()
                )
            );

            if ($result->rowCount() > 0) {
                $post_installing = false;
            }
        }
    }
}
