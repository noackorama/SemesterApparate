<?php
// +---------------------------------------------------------------------------+
// ESAFolder.class.php
// class for managing ESA document folder
//
// Copyright (c) 2007 André Noack <noack@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once 'lib/classes/SimpleORMap.class.php';

/**
* class for managing ESA document folder
*
* see lib/classes/SimpleORMap.class.php
*
* @access    public
* @author    André Noack <noack@data-quest.de>
* @version    $Id:$
* @package    esesemesterapparate
*/
class EsaFolder extends SimpleORMap {

    function __construct($id = null)
    {
        $this->db_table = 'esa_folder';
        $this->default_values['accesstime_start'] = 0;
        $this->default_values['accesstime_end'] = 0;
        parent::__construct($id);
    }

    function checkAccessTime(){
        $now = time();
        return ($now > $this->getValue('accesstime_start') && ($now < $this->getValue('accesstime_end') || !$this->getValue('accesstime_end')));
    }
}
?>
