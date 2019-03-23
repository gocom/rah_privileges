<?php

/*
 * rah_privileges - Configure admin-side privileges
 * https://github.com/gocom/rah_privileges
 *
 * Copyright (C) 2019 Jukka Svahn
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
 * along with hpw_admincss. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * The plugin class.
 *
 * @internal
 */
final class Rah_Privileges
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        global $event;

        add_privs('prefs.rah_privs', '1');
        register_callback(array($this, 'uninstall'), 'plugin_lifecycle.rah_privileges', 'deleted');
        register_callback(array($this, 'addLocalization'), 'prefs', '', 1);

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
        global $textarray, $txp_permissions;

        $active = [];

        // Create a preferences string for every privilege that exists.

        foreach ($txp_permissions as $resource => $privs) {
            $name = 'rah_privileges_' . md5($resource);
            $textarray[$name] = $resource;

            // Add panel name infront of the list.
            $privs = do_list($privs);
            array_unshift($privs, $resource);
            $privs = implode(', ', $privs);

            if (get_pref($name, false) === false) {
                set_pref($name, $privs, 'rah_privs', PREF_PLUGIN, 'rah_privileges_input', 80);
            }

            $active[] = $name;
        }

        // Remove privileges that no longer exist.

        if ($active) {
            $active = implode(',', quote_list((array) $active));

            safe_delete(
                'txp_prefs',
                "name like 'rah\_privileges\_%' and name not in({$active})"
            );
        }
    }

    /**
     * Add panel titles into the translation array as pref labels.
     */
    public function addLocalization()
    {
        global $textarray;

        $resources = [];

        foreach (areas() as $area => $events) {
            foreach ($events as $title => $resource) {
                $name = 'rah_privileges_' . md5($resource);
                $textarray[$name] = $title;
            }
        }

        // Update field sorting index.

        foreach ($textarray as $name => $string) {
            if (strpos($name, 'rah_privileges_') === 0) {
                $resources[$name] = $string;
            }
        }

        $index = 1;
        asort($resources);

        foreach ($resources as $name => $resource) {
            update_pref($name, null, null, null, null, $index++);
        }
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
}
