<?php
/**
 * Access to countries, subdivisions and cities
 * 
 * @package    wsCat Jx
 * @version    1.0
 * @since      24.10.2008
 * @copyright  2004-2008 5Dev
 * @link       http://5dev.com
 */
require_once 'Model/Geografy/geoip.inc';
require_once 'Model/Geografy/geoipcity.inc';
require_once 'Model/Geografy/geoipregionvars.php';

class Model_Geografy_Main
{
    private $moDb;

    /**
     * Database table for country subdivisions
     * @var string
     */
    public $mSubDiv;

    /**
     * Database table for countries
     * @var string
     */
    public $mCntr;

    /**
     * Database table for cities
     * @var string
     */
    public $mCity;


    /**
     * Constructor. Iniatilize base class variables
     *
     * @param array  $glObj    
     * @param array  $tables
     * @return void
     */
    public function __construct(&$glObj,  $tables = null)
    {
        $this -> moDb =& $glObj['gDb'];

        if (!$tables)
        {
            $this -> mCntr   = TB.'countries';
            $this -> mSubDiv = TB.'countries_subdivisions';
            $this -> mCity   = TB.'countries_cities';
        }
        else
        {
            $this -> mCntr   = $tables['cntr'];
            $this -> mSubDiv = $tables['sd'];
            $this -> mCity   = $tables['city'];
        }
    }

    public function &SearchCity($query, $country_code = '')
    {
        $query = $query.'%';
        $res = $this -> moDb -> getAssoc('SELECT cy.city_id, cy.name AS name, cy.iso2_cntr, sd.name AS sd_name, sd.subdiv_id
                                          FROM '.$this -> mCity .'  cy
                                               LEFT JOIN '.$this -> mSubDiv.' sd ON (sd.subdiv_id = cy.subdiv_id)
                                          WHERE (LOWER(cy.name) LIKE ? OR LOWER(cy.name) LIKE ?)
                                          '.($country_code ? ' AND LOWER(cy.iso2_cntr) = "'.$country_code.'"' : '').'
                                          ORDER BY cy.name ASC, cy.iso2_cntr ASC
                                          LIMIT 0,10', true, array($query, $query,));;
        return $res;
    }
    

    public function &SearchState($query)
    {
        $query = $query.'%';
        $res = $this -> moDb -> getAssoc('SELECT sd.subdiv_id, sd.name, sd.iso2_cntr
                                          FROM '.$this -> mSubDiv.' sd
                                          WHERE sd.name LIKE ?
                                          ORDER BY sd.name ASC, sd.iso2_cntr ASC
                                          LIMIT 0,10', true, array($query));

        return $res;

    }

    public function &SearchCountry($query)
    {
        $query = '%'.$query.'%';
        $res = $this -> moDb -> getAssoc('SELECT cn.iso2, cn.name
                                          FROM '.$this -> mCntr .' cn
                                          WHERE cn.name LIKE ?
                                          ORDER BY cn.name ASC
                                          LIMIT 0,10', false, array($query));

        return $res;

    }

    public function GeoIPInit()
    {
        if (!defined('IP'))
           throw new Model_Geografy_MainException(1);

        // ------------------------------------------------------------------------=
        // GeoIP Initialization
        $AfricaArr = array('AO','BJ','BW','BF','BI','GA','GM','GH','GN','GW','DJ','ZM','EH','ZW','CV','CM','KE','KM','CG','CD','CI','LS','LR','LY','MU','MR','MG','MW','ML','MA','MZ','NA','NE','NG','RE','RW','ST','SZ','SH','SC','SN','SO','SD','SL','TZ','TG','TN','UG','CF','TD','GQ','ER','ET');
        
        $mu = array(); // TODO: Special users from special country
       
        if (!empty($_COOKIE['geo']['saved_ip']) && $_COOKIE['geo']['saved_ip'] == IP 
            && !empty($_COOKIE['geo']['cntr_by_ip']) && preg_match('/^[A-Z]{2}$/', $_COOKIE['geo']['cntr_by_ip'])
           )
        {
           $selCountry = $_COOKIE['geo']['cntr_by_ip'];
       
           if (!empty($_COOKIE['geo']['city_by_ip']))
           {
                define('SEL_ACT1'     , true);
                define('SEL_CITY'     , $_COOKIE['geo']['city_by_ip']);
                define('SEL_CITY_ID'  , (int)$_COOKIE['geo']['city_id_by_ip']);
       
                define('SEL_SUBDIV'   , @$_COOKIE['geo']['subdiv_by_ip']);
                define('SEL_SUBDIV_ID', (int)@$_COOKIE['geo']['subdiv_id_by_ip']);
                define('SEL_ZIP'      , @$_COOKIE['geo']['zip_by_ip']);
       
                if (!in_array(UI_UID, $mu) && ('US' == $selCountry || 'CA' == $selCountry || 'GB' == $selCountry))
                {
                    define('SEL_ACT2' , true);
                    define('SEL_FULL1', $_COOKIE['geo']['full1_by_ip']);
                    define('SEL_FULL2', $_COOKIE['geo']['full2_by_ip']);
                }
                else
                {
                   $mu = array();
                   define('SEL_ACT2', false);
                }
           }
           else
           {
               $mu = array();
               define('SEL_ACT1', false);
               define('SEL_ACT2', false);
           }
        }
        else
        {
            $gi = geoip_open(dirname(__FILE__).'/'.'GeoIPCity.dat',GEOIP_STANDARD);
            
            if (!empty($gi))
            {
               $record = geoip_record_by_addr($gi,IP);
               
               if (!empty($record))
               {
                   $selCountry = $record -> country_code;
              
                   define('SEL_ACT1'  , true);
                   define('SEL_SUBDIV', $record -> region);
                   if ($record -> region)
                   {
                       define('SEL_SUBDIV_ID', (int)$this -> moDb -> getOne('SELECT subdiv_id 
                                                                    FROM '.$this -> mSubDiv .' 
                                                                    WHERE iso2_cntr = \''.$selCountry.'\' 
                                                                           AND code = \''.$record -> region.'\''));
              
                       if (SEL_SUBDIV_ID)
                           define('SEL_CITY_ID', (int)$this -> moDb -> getOne('SELECT city_id
                                                                      FROM '.$this -> mCity.'
                                                                      WHERE subdiv_id = '.SEL_SUBDIV_ID.' 
                                                                            AND name = ?', array($record -> city)));
                       else
                           define('SEL_CITY_ID'  , 0);
              
                   }
                   else
                   {
                       define('SEL_SUBDIV_ID', 0);
                       define('SEL_CITY_ID'  , 0);
                   }
              
                   define('SEL_CITY'  , $record -> city);
                   define('SEL_ZIP'   , $record -> postal_code);
       
                   setcookie('geo[cntr_by_ip]'     , $selCountry   , NOW_TIME + 15552000, '/');
                   setcookie('geo[subdiv_by_ip]'   , SEL_SUBDIV    , NOW_TIME + 15552000, '/');
                   setcookie('geo[subdiv_id_by_ip]', SEL_SUBDIV_ID , NOW_TIME + 15552000, '/');
                   setcookie('geo[city_by_ip]'     , SEL_CITY      , NOW_TIME + 15552000, '/');
                   setcookie('geo[city_id_by_ip]'  , SEL_CITY_ID   , NOW_TIME + 15552000, '/');
                   setcookie('geo[zip_by_ip]'      , SEL_ZIP       , NOW_TIME + 15552000, '/');
                  
                   if (!in_array(UI_UID, $mu) && ('US' == $selCountry || 'CA' == $selCountry || 'UK' == $selCountry))
                   {
                       define('SEL_ACT2' , true);
                       define('SEL_FULL1', SEL_CITY.', '.SEL_SUBDIV.', '.$record -> country_name);
                       define('SEL_FULL2', SEL_CITY.', '.@$ISO[$record->country_code][$record->region]);
                  
                       setcookie('geo[full1_by_ip]', SEL_FULL1, NOW_TIME + 15552000, '/');
                       setcookie('geo[full2_by_ip]', SEL_FULL2, NOW_TIME + 15552000, '/');
                   }
                   else
                   {
                       $mu = array();
                       setcookie('geo[full1_by_ip]');
                       setcookie('geo[full2_by_ip]');
                       define('SEL_ACT2', false);
                   }
                }
                else
                {
                    $mu = array();
                    $selCountry = 'US';
                    define('SEL_ACT1', false);
                    define('SEL_ACT2', false);
                    setcookie('geo[city_by_ip]');
                    setcookie('geo[city_id_by_ip]');
                    setcookie('geo[subdiv_by_ip]');
                    setcookie('geo[subdiv_id_by_ip]');
                    setcookie('geo[zip_by_ip]');
                    setcookie('geo[full1_by_ip]');
                    setcookie('geo[full2_by_ip]');
                }
            
                geoip_close($gi);
            }
            else
                $selCountry = 'US';

            setcookie('geo[saved_ip]'  , IP         , NOW_TIME + 15552000, '/');
            setcookie('geo[cntr_by_ip]', $selCountry , NOW_TIME + 15552000, '/');
        }
       
        define('SEL_COUNTRY', $selCountry);
    }

    public function GetSubDivByCity($city_id)
    {
        return $this -> moDb -> getOne('SELECT subdiv_id
                                        FROM '.$this -> mCity.'
                                        WHERE city_id = '.$city_id);
    }

    public function GetInfoBySubDiv($subdiv_id)
    {
        return $this -> moDb -> getRow('SELECT cr.name AS cntr_name,
                                               cs.name AS subdiv_name,
                                               cs.iso2_cntr
                                        FROM '.$this -> mSubDiv.' cs,
                                             '.$this -> mCntr.' cr
                                        WHERE cs.subdiv_id = '.$subdiv_id.'
                                              AND cr.iso2 = cs.iso2_cntr');
    }

    /**
     * Get all subdivisions for specified country
     * @param string $iso2_cntr unique country iso2-code
     * @return array 
     */
    public function &GetSubDiv($iso2_cntr)
    {
        $res = $this -> moDb -> getAll('SELECT subdiv_id, name
                                        FROM '.$this->mSubDiv.'
                                        WHERE iso2_cntr = ?
                                        ORDER BY name ASC', array($iso2_cntr));
        return $res;

    }

    /**
     * Get all countries
     * @return array 
     */
    public function &GetCountries($assoc = 0)
    {
        $res = $this -> moDb -> getAll('SELECT iso2, name AS name, sortid
                                        FROM '.$this->mCntr.'
                                        ORDER BY sortid DESC, name ASC');
        if ($assoc)
        {
            $re = array();
            foreach ($res as $v)
            {
                $re[$v['iso2']] = $v['name'];
            }
            return $re;
        }
        return $res;

    }

    /**
     * Get all cities for specified country
     * @param string $iso2_cntr iso2 code of country
     * @return array 
     */
    public function &GetCities($iso2_cntr, $subdiv_id = 0)
    {
        if (0 < $subdiv_id)
            $res = $this -> moDb -> getAll('SELECT city_id, name
                                            FROM '.$this->mCity.'
                                            WHERE subdiv_id = ?
                                            ORDER BY name ASC', array($subdiv_id));
        else
        {
            $res = $this -> moDb -> getAll('SELECT city_id, name
                                            FROM '.$this->mCity.'
                                            WHERE iso2_cntr = ?
                                            ORDER BY name ASC', array($iso2_cntr));
        }
        return $res;

    }


    public function GetCitiesCount($iso2 = '', $str = '')
    {
        $sql = 'SELECT COUNT(city_id) FROM '.$this -> mCity.' WHERE city_id = city_id';
        
        if ($iso2 && 2==strlen($iso2))
        {
            $sql .= ' AND LOWER(iso2_cntr) = "'.mysql_escape_string(ToLower($iso2)).'"';
        }

        if ($str)
        {
            $str = mysql_escape_string( ToLower(strip_tags($str)) );
            $sql .= ' AND (LOWER(name) LIKE "%'.$str.'%" OR LOWER(name_wo_diacritics) LIKE "%'.$str.'%")';
        }
        return $this -> moDb -> getOne($sql);
    }

    public function GetCitiesList( $iso2 = '', $first = 0, $cnt = 0, $str = '')
    {
        $sql = 'SELECT c.*, cy.name AS country, s.name AS subdiv
                FROM '.$this -> mCity.' c
                LEFT JOIN '.$this -> mSubDiv.' s ON (s.subdiv_id = c.subdiv_id)
                , '.$this -> mCntr.' cy
                WHERE c.iso2_cntr = cy.iso2';

        if ($iso2 && 2==strlen($iso2))
        {
            $sql .= ' AND LOWER(c.iso2_cntr) = "'.mysql_escape_string(ToLower($iso2)).'"';
        }

        if ($str)
        {
            $str = mysql_escape_string( ToLower(strip_tags($str)) );
            $sql .= ' AND (LOWER(c.name) LIKE "%'.$str.'%" OR LOWER(c.name_wo_diacritics) LIKE "%'.$str.'%")';
        }

        $sql .= ' ORDER BY c.iso2_cntr, c.name';
        if ($cnt)
        {
            $db  =$this -> moDb -> limitQuery($sql, $first, $cnt);
        }
        else
        {
            $db  =$this -> moDb -> query($sql);
        }

        $r = array();        
        while ($row = $db -> FetchRow())
        {
            $r[] = $row;
        }
        return $r;
    }

    public function GetCity($city_id)
    {
        return $this -> moDb -> getRow('SELECT * FROM '.$this -> mCity.' WHERE city_id = ?', array($city_id));
    }



    public function DelCity($id)
    {
        $this -> moDb -> query('DELETE FROM '.$this -> mCity.' WHERE city_id = ?', array($id));
        return true;
    }


    public function EditCity($id = 0,  $name, $iso2, $subdiv)
    {
        $subdiv_id = 0;
        if ($subdiv)
        {
            $subdiv_id = $this -> moDb -> getOne('SELECT subdiv_id FROM '.$this -> mSubDiv.' WHERE iso2_cntr = ? AND code = ?', 
                                    array($iso2, $subdiv));
        }



        if (!$id)
        {
            $sql = 'INSERT INTO '.$this -> mCity.' (name, name_ru, name_wo_diacritics,
                    iso2_cntr, subdiv_id, subdivision,
                    status) VALUES(?, ?, ?, ?, ?, ?, ?)';
            $this -> moDb -> query($sql,
                array($name, $name, $name, $iso2, $subdiv_id, $subdiv, 'AI'));
        }
        else
        {
            $sql = 'UPDATE '.$this -> mCity.' SET name = ?, name_ru = ?,
                    name_wo_diacritics = ?,
                    iso2_cntr = ?, subdiv_id = ?, subdivision = ?
                    WHERE city_id = ?';
            $this -> moDb -> query($sql,
                array($name, $name, $name, $iso2, $subdiv_id, $subdiv, $id));
        }
        return true;
    }


    public function GetCountryName($iso2_cntr)
    {
        return $this -> moDb -> getOne('SELECT name 
                                        FROM '.$this->mCntr.'
                                        WHERE iso2 = ?', array($iso2_cntr));

    }

    public function GetSubDivName($subdiv_id)
    {
        return $this -> moDb -> getRow('SELECT name, iso2_cntr
                                        FROM '.$this->mSubDiv.'
                                        WHERE subdiv_id = ?', array($subdiv_id));

    }

    public function GetCityName($city_id)
    {
        return $this -> moDb -> getRow('SELECT cy.name AS city_name, 
                                               cr.iso2, cr.name AS country_name,
                                               cs.name AS div_name,
                                               cs.code AS div_code
                                        FROM '.$this->mCity.' cy
                                        LEFT JOIN '.$this->mSubDiv.' cs ON (cs.subdiv_id=cy.subdiv_id),
                                        '.$this->mCntr.' cr
                                        WHERE cy.city_id = ?
                                              AND cr.iso2=cy.iso2_cntr', array($city_id));

    }

    public function GetPlaces($iso2_cntr, $current, $subdiv_id = 0, $limit = true)
    {
        $places = array();
        $country_name = $this -> GetCountryName($iso2_cntr);

        if (!$subdiv_id)
            $dbout = $this -> moDb -> query('SELECT mcy.city_id, mcy.subdiv_id,
                                                    mcy.name, 
                                                    cs.name AS div_name,
                                                    cs.code AS div_code
                                             FROM '.$this -> mCity.' mcy,
                                                  '.$this -> mSubDiv.' cs
                                             WHERE mcy.iso2_cntr = \''.$iso2_cntr.'\'
                                                   AND cs.subdiv_id = mcy.subdiv_id
                                             LIMIT 0,7');
        else
        {
            if ($limit)
                $dbout = $this -> moDb -> query('SELECT mcy.city_id, mcy.subdiv_id,
                                                        mcy.name, 
                                                        cs.name AS div_name,
                                                        cs.code AS div_code
                                                 FROM '.$this -> mCity.' mcy,
                                                      '.$this -> mSubDiv.' cs
                                                 WHERE mcy.iso2_cntr = \''.$iso2_cntr.'\'
                                                       AND cs.subdiv_id = mcy.subdiv_id
                                                       AND cs.subdiv_id = '.$subdiv_id.'
                                                 LIMIT 0,7');
            else
                $dbout = $this -> moDb -> query('SELECT mcy.city_id, mcy.subdiv_id,
                                                        mcy.name, 
                                                        cs.name AS div_name,
                                                        cs.code AS div_code
                                                 FROM '.$this -> mCity.' mcy,
                                                      '.$this -> mSubDiv.' cs
                                                 WHERE mcy.iso2_cntr = \''.$iso2_cntr.'\'
                                                       AND cs.subdiv_id = mcy.subdiv_id
                                                       AND cs.subdiv_id = '.$subdiv_id.'
                                                 ORDER BY mcy.name');
        }
           

        $codes = array();
        while ($row = $dbout -> fetchRow())
        {
            $codes[]  = $iso2_cntr.'_'.$row['subdiv_id'].'_'.$row['city_id'];
            $places[] = array(
                        'id'   => $row['city_id'],
                        'code' => $iso2_cntr.'_'.$row['subdiv_id'].'_'.$row['city_id'],
                        'name' => (('N73' == $row['div_code'] || 'N74' == $row['div_code']) ? $row['name'].', '.$country_name:$row['name'].', '.$row['div_name'])
                        );
        }

        $matches = array();

        if (!empty($current))
        {
            if (is_numeric($current))
            {
                $dbout = $this -> moDb -> query('SELECT mcy.city_id, mcy.subdiv_id, mcy.iso2_cntr,
                                                        mcy.name, mcn.name AS country_name,
                                                        cs.name AS div_name,
                                                        cs.code AS div_code
                                                 FROM '.$this -> mCity.'   mcy,
                                                      '.$this -> mCntr.'   mcn,
                                                      '.$this -> mSubDiv.' cs
                                                 WHERE mcy.city_id  = '.$current.'
                                                       AND mcn.iso2 = mcy.iso2_cntr
                                                       AND cs.subdiv_id = mcy.subdiv_id');
                $row = $dbout -> fetchRow();
                if ($row)
                   $places[] = array(
                                'id'   => $row['city_id'],
                                'code' => $row['iso2_cntr'].'_'.$row['subdiv_id'].'_'.$row['city_id'],
                                'name' => (('N73' == $row['div_code'] || 'N74' == $row['div_code']) ? $row['name'].', '.$row['country_name']:$row['name'].', '.$row['div_name'])
                                );
                        
            }
            elseif ('XX_0_0' != $current 
                && 'XX_1_1' != $current 
                && preg_match('/^([A-Z]{2})_(\d+)_(\d+)$/', $current, $matches)
                && !in_array($current, $codes)
               )
            {
                if (0 < $matches[2])
                {
                    if (0 < $matches[3])
                    {
                        $dbout = $this -> moDb -> query('SELECT mcy.city_id, mcy.subdiv_id, mcy.iso2_cntr,
                                                                mcy.name, mcn.name AS country_name,
                                                                cs.name AS div_name,
                                                                cs.code AS div_code
                                                         FROM '.$this -> mCity.'   mcy,
                                                              '.$this -> mCntr.'   mcn,
                                                              '.$this -> mSubDiv.' cs
                                                         WHERE mcy.city_id  = '.$matches[3].'
                                                               AND mcn.iso2 = mcy.iso2_cntr
                                                               AND cs.subdiv_id = mcy.subdiv_id');
                        $row = $dbout -> fetchRow();
                        if ($row)
                           $places[] = array(
                                        'id'   => $row['city_id'],
                                        'code' => $row['iso2_cntr'].'_'.$row['subdiv_id'].'_'.$row['city_id'],
                                        'name' => (('N73' == $row['div_code'] || 'N74' == $row['div_code']) ? $row['name'].', '.$row['country_name']:$row['name'].', '.$row['div_name'])
                                        );
                    }
                    else
                    {
                        $dbout = $this -> moDb -> query('SELECT mcn.name AS country_name,
                                                                cs.subdiv_id, cs.iso2_cntr,
                                                                cs.name AS div_name
                                                         FROM '.$this -> mCntr.'   mcn,
                                                              '.$this -> mSubDiv.' cs
                                                         WHERE cs.subdiv_id = '.$matches[2].'
                                                               AND mcn.iso2  = cs.iso2_cntr');
                        $row = $dbout -> fetchRow();
                        if ($row)
                           $places[] = array(
                                        'id'   => 0,
                                        'code' => $row['iso2_cntr'].'_'.$row['subdiv_id'].'_0',
                                        'name' => $row['div_name'].', '.$row['country_name']
                                        );
                    }
                }
                else
                {
                    $name = $this -> GetCountryName($matches[1]);
                    if ($name)
                       $places[] = array(
                                    'id'   => 0,
                                    'code' => $matches[1].'_0_0',
                                    'name' => $name
                                    );
                }
            }
        }

        return $places;
    }

    public function CheckCity( $city, $country = '', $region = '' )
    {
        $city    = trim( ToLower( mysql_escape_string($city) ) );
        $country = trim( ToLower( mysql_escape_string($country) ) );
        $region  = trim( ToLower( mysql_escape_string($region) ) );

        $sql = 'SELECT cy.* 
                '.($country ? ', c.name AS country_name' : '')
                 .($region ? ', s.name AS region_name' : '')
               .' FROM '.$this -> mCity.' cy
               '.($country ? ', '.$this -> mCntr.' c' : '').
               ($region ? ', '.$this -> mSubDiv.' s' : '').
               ' WHERE (LOWER(cy.name) LIKE ?)'
                .($country ? ' AND cy.iso2_cntr = c.iso2 AND c.name LIKE ? ' : '').
                 ($region ? ' AND cy.subdiv_id = s.subdiv_id AND s.name LIKE ? ' : '');
        $ar[] = $city;
        
        if ($country)
        {
            $ar[] = $country;
        }
        if ($region)
        {
            $ar[] = $region;
        }
        $db = $this -> moDb -> query($sql, $ar);
        $r  = array();
        while ($row = $db -> FetchRow())
        {
            if (empty($row['country_name']))
            {
                $row['country_name'] = $this -> moDb -> getOne('SELECT name FROM '.$this -> mCntr.' WHERE iso2 = ?', array($row['iso2_cntr']));     
            }
            $r[] = $row;
        }
        return $r;
    }/** $CheckCity */

}


class Model_Geografy_MainException extends ExtException
{
    public function __construct($errCodes)
#    public public function __construct($errCodes)
    {
        parent::__construct($errCodes);
    }
}
?>