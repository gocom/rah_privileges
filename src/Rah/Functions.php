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
 * Renders input for setting privilege settings.
 *
 * @param  string $name  The name
 * @param  string $value The value
 * @return string HTML widget
 */
function rah_privileges_input($name, $value)
{
    $field = $name . '[]';
    $levels = get_groups();
    $groups = do_list($value);
    $resource = array_shift($groups);
    $out = [];

    unset($levels[0]);
    $out[] = hInput($field, $resource);
    $out[] = tag_start('div', ['class' => 'txp-grid']);

    foreach ($levels as $group => $label) {
        $id = $name . '_' . intval($group);
        $checked = in_array($group, $groups);

        $out[] = tag(
            checkbox(
                $field,
                $group,
                $checked,
                '',
                $id
            ) . ' ' .
            tag($label, 'label', array('for' => $id)),
            'span'
        ) . ' ';
    }

    $out[] = tag_end('div');

    return implode('', $out);
}
