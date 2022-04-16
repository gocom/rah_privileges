<?php

/*
 * rah_privileges - Configure admin-side privileges
 * https://github.com/gocom/rah_privileges
 *
 * Copyright (C) 2022 Jukka Svahn
 *
 * This file is part of rah_privileges.
 *
 * rah_privileges is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_privileges is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_privileges. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * The plugin class.
 *
 * @internal
 */
final class Rah_Privileges
{
    /**
     * Preference fields.
     *
     * @var array|null
     */
    private $fields = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $event;

        add_privs('prefs.rah_privs', '1');
        register_callback([$this, 'uninstall'], 'plugin_lifecycle.rah_privileges', 'deleted');
        register_callback([$this, 'addLocalization'], 'prefs', '', 1);

        if ($event === 'prefs') {
            $this->syncPrefs();
        }

        $this->mergePrivileges();
    }

    /**
     * Uninstaller.
     */
    public function uninstall()
    {
        safe_delete('txp_prefs', "name like 'rah\_privileges\_%'");
    }

    /**
     * Syncs preference fields.
     *
     * Creates preference keys for each permission resource. This
     * is how we get the fields to show up in the interface.
     */
    public function syncPrefs()
    {
        global $txp_permissions;

        $strings = [];

        // Create a preferences string for every privilege that exists.
        foreach ($this->getFields() as $resource => $name) {
            $strings[$name] = $resource;
            $privs = $txp_permissions[$resource] ?? '';

            // Add panel name infront of the list.
            $privs = do_list($privs);
            array_unshift($privs, $resource);
            $privs = implode(', ', $privs);

            if (get_pref($name, false) === false) {
                set_pref($name, $privs, 'rah_privs', PREF_PLUGIN, 'rah_privileges_input', 80);
            }
        }

        Txp::get('\Textpattern\L10n\Lang')->setPack($strings, true);

        $this->cleanPrefs();
    }

    /**
     * Add panel titles into the translation array as pref labels.
     */
    public function addLocalization()
    {
        // Load user group labels.
        $strings = Txp::get('\Textpattern\L10n\Lang')->extract($this->getLanguageCode(), 'admin');

        $fields = $this->getFields();

        foreach (areas() as $area => $events) {
            foreach ($events as $title => $resource) {
                $name = $fields[$resource] ?? null;

                if ($name) {
                    $strings[$name] = \sprintf(
                        '%s<br/><small>%s</small>',
                        $title,
                        $resource
                    );
                }
            }
        }

        Txp::get('\Textpattern\L10n\Lang')->setPack($strings, true);

        $this->sort();
    }

    /**
     * Update preference field sorting order.
     */
    private function sort()
    {
        $index = 1;

        foreach ($this->getFields() as $resource => $name) {
            update_pref($name, null, null, null, null, $index++);
        }
    }

    /**
     * Get the current language code.
     *
     * @return string
     */
    private function getLanguageCode()
    {
        return get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);
    }

    /**
     * Merges permissions table with our overwrites.
     */
    public function mergePrivileges()
    {
        global $prefs, $txp_permissions, $event;

        foreach ($prefs as $name => $value) {
            if (strpos($name, 'rah_privileges_') !== 0) {
                continue;
            }

            $groups = do_list($value);
            $resource = array_shift($groups);
            $groups = implode(',', $groups);

            if ($event === 'prefs' && strpos($resource, 'prefs') === 0) {
                continue;
            }

            if (!$groups) {
                $txp_permissions[$resource] = null;
            } else {
                $txp_permissions[$resource] = $groups;
            }
        }
    }

    /**
     * Gets fields.
     *
     * @return array<string, string>
     */
    private function getFields(): array
    {
        global $txp_permissions;

        if ($this->fields === null) {
            $this->fields = [];

            foreach ($txp_permissions as $resource => $privs) {
                $this->fields[$resource] = 'rah_privileges_' . md5($resource);
            }

            ksort($this->fields);
        }

        return $this->fields;
    }

    /**
     * Clean preferences.
     */
    private function cleanPrefs()
    {
        $active = $this->getFields();

        // Remove privileges that no longer exist.
        if ($active) {
            $active = implode(',', quote_list((array) $active));

            safe_delete(
                'txp_prefs',
                "name like 'rah\_privileges\_%' and name not in({$active})"
            );
        }
    }
}
