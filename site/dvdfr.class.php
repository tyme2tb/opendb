<?php
/* 	
	Open Media Collectors Database
	Copyright (C) 2001,2006 by Jason Pell

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
	* 
	* Search for 'Rambo' to return a list of various titles.
		Search for '12 Angry Men' to get an exact title match.
		Search for 'faddsda' to trigger a search error.
*/
include_once("./functions/SitePlugin.class.inc");

$dvdfr_month_map = array(
         "janvier" => "01",
		 "f�vrier" => "02",
		 "mars" => "03",
		 "avril" => "04",
		 "mai" => "05",
		 "juin" => "06",
		 "juillet" =>"07",
		 "aout" => "08",
		 "ao�t" => "08",
		 "septembre" => "09",
		 "octobre" => "10",
		 "novembre" => "11",
		 "d�cembre" => "12"
         );

$dvdfr_rating_map = array(
         "1" => "G",
		 "2" => "PG",
		 "3" => "M",
		 "4" => "MA",
		 "5" => "R",
		 "6" => "X",
		 "7" => "NR" 
         );

/* 
  Shamelessly copied from the previous dvdfr plugin for OpenDB 
*/
$dvdfr_language_map =   array (
                "Afghan"=>"",
                "Allemand"=>"GERMAN",
                "Anglais"=>"ENGLISH",
                "Anglais (australien)"=>"ENGLISH",
                "Arabe"=>"ARABIC",
                "Bengali"=>"",
                "Breton"=>"",
                "Cantonais"=>"",
                "Chinois"=>"CHINESE",
                "Cantonais"=>"CANTONESE",
                "Cor�en"=>"KOREAN",
                "Cr�ole"=>"",
                "Croate"=>"CROATIAN",
                "Danois"=>"DANISH",
                "Espagnol"=>"SPANISH",
                "Farsi"=>"",
                "Finlandais"=>"FINNISH",
                "Flamand"=>"DUTCH",
                "Fran�ais"=>"FRENCH",
                "Grec"=>"GREEK",
                "H�breu"=>"HEBREW",
                "Hindi"=>"HINDOE",
                "Hongrois"=>"HUNGARIAN",
                "International"=>"",
                "Inuit"=>"",
                "Islandais"=>"ISLANDIC",
                "Italien"=>"ITALIAN",
                "Japonais"=>"JAPANESE",
                "Kurde"=>"",
                "Malien"=>"",
                "Mandarin"=>"MANDARIN",
                "Musique"=>"",
                "N�erlandais"=>"DUTCH",
                "Norv�gien"=>"NORWEGIAN",
                "Polonais"=>"POLISH",
                "Portugais"=>"PORTUGUESE",
                "Roumain"=>"",
                "Russe"=>"",
                "Serbe"=>"",
                "Su�dois"=>"SWEDISH",
                "Taiwanais"=>"",
                "Tch�que"=>"CZECH",
                "Tibetain"=>"",
                "Tunisien"=>"",
                "Turc"=>"TURKISH",
                "Vietnamien"=>""
            );

//
// Search for 'hophop un lapin' to return no titles.
// Search for 'Au-dela de la gloire' to get an exact title match.
//
class dvdfr extends SitePlugin
{
	function dvdfr($site_type)
	{
		parent::SitePlugin($site_type);
	}
	
	function queryListing($page_no, $items_per_page, $offset, $s_item_type, $search_vars_r)
	{
	    if(strlen($search_vars_r['dvdfr_id'])>0)
		{
			$pageBuffer = $this->fetchURI("http://www.dvdfr.com/dvd/dvd.php?id=".$search_vars_r['dvdfr_id']);

			if (strlen($pageBuffer)>0)
				$this->addListingRow(NULL,NULL,NULL,array('dvdfr_id'=>$search_vars_r['dvdfr_id']));
            
			return TRUE;
		}
		else
		{
			$pageBuffer = $this->fetchURI("http://www.dvdfr.com/search/multisearch.php?multiname=".urlencode($search_vars_r['title'])); 
		}
            
		if (strlen($pageBuffer)>0)
		{
			$pageBuffer = preg_replace('/[\r\n]+/', ' ', $pageBuffer);
			
			if (preg_match_all("!<td colspan=\"2\" class=\"dvdTitle\">\s*<a title=\"[^\"]*\" href=\"([^\"]*)\">([^<]*)</a>!",$pageBuffer,$matches))
			{
				//print_r($matches);
				for ($i = 0; $i < count($matches[2]); $i++)
				{
					$href = $matches[1][$i];
					
					if(preg_match("!f([0-9]*)!", $href, $regs))
					{
						$dvdfr_id = $regs[1];
					
						//<a href="../dvd/f34593_rambo_iii.html"><img src="../sodr.php?id=34593&amp;p=7" alt="Rambo III" border="0" height="70" width="50"></a>
						if(preg_match("!<a href=\"".preg_quote($href)."\"><img src=\"([^\"]*)\"!", $pageBuffer, $regs))
						{
							$thumbimg = str_replace("../", "http://www.dvdfr.com/", $regs[1]);
						}
			    		
						$this->addListingRow($matches[2][$i], $thumbimg, NULL, array('dvdfr_id'=>$dvdfr_id));
					}
				}
			}

			// default
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	function queryItem($search_attributes_r, $s_item_type)
	{
	    $pageBuffer = $this->fetchURI("http://www.dvdfr.com/dvd/dvd.php?id=".$search_attributes_r['dvdfr_id']);

		// no sense going any further here.
		if(strlen($pageBuffer)==0)
		    return FALSE;

        // YEAR VID_FORMAT SUBTITLES(x) RUN_TIME RATIO NO_DISCS MOVIE_PLOT IMDB_ID IMAGEURL DVD_REGION DIRECTOR AUDIO_LANG(x) AGE_RATING ACTORS

        $startblock="<!-- END:AdSolution-Tag 4.1 -->";

        $startblockPos=strpos($pageBuffer,$startblock);

        $parseblock = substr($pageBuffer, $startblockPos);
        
        //<div class="dvd_title">RAMBO</div>
        //<div class="dvd_titlevo">First Blood</div>
        
        // We look for the title
        if (preg_match("/class=\"dvd_title\">(.*)</i", $parseblock, $regs))
		{
            // $regs[1] could contain some extra info than plain title.
            // we want it added to the blurb
            if (preg_match("/(.*) \((.*)\)/i",$regs[1],$blurb))
			{
                $title = $blurb[1];
                $tblurb = $blurb[2];
            }
			else
			{
                $title = $regs[1];
            }
            
            if (strlen($tblurb)>0) 
                $this->addItemAttribute('dvd_extras',$tblurb);
                
            $title = str_replace("\"", "", $title);
            
            $this->addItemAttribute('title', $title);
        }
        
        if (preg_match("/class=\"dvd_titlevo\">(.*)</i", $parseblock, $regs))
		{
			$this->addItemAttribute('alt_title', $regs[1]);
		}
        
        // Cover URL - OK
        if (preg_match(":src=\"../(images/dvd/cover.*\.jpg)\":i",$parseblock,$regs))
		{
            $this->addItemAttribute('imageurl',"http://www.dvdfr.com/".$regs[1]);
        }
        
        // Age rating
        if (preg_match("@<img src=\"../images/ratings/(\d).gif@i",$parseblock,$regs))
		{
		  	global $dvdfr_rating_map;
		  	
            $this->addItemAttribute('age_rating',$dvdfr_rating_map[$regs[1]]);
        }

        // Video encoding format
        // Should be PAL or SECAM
        if (preg_match("@title=\"Standard vid�o du DVD\" vspace=\"\d\">\n[\s\t]+<div align=\"center\"><center>\n[\s\d]+([A-Z]+)@i",$parseblock,$regs))
		{
            $this->addItemAttribute('vid_format',trim($regs[1]));
        }
         
        // Year or production for the movie - OK
        if (preg_match("/class=\"dvd_titleinfo\">(\w*) ?, ?(\d*)</i", $parseblock, $regs))
		{
            $this->addItemAttribute('year',$regs[2]);
        }

        // Release date for the dvd => dvd_text
        if (preg_match(":(S|s)orti le</div>\n.*class=\"dvd_text\"> *(\d*) *(\w*) *(\d*)<:im", $parseblock, $regs))
		{
			global $dvdfr_month_map;
		  	
			$reldate=$regs[2].":".$dvdfr_month_map[$regs[3]].":".$regs[4];
			if ($regs[2] < 10) {
                $reldate="0".$reldate;
			} 
			$this->addItemAttribute('rel_dvd_dt', $reldate);
        }

        // Length of the movie - OK
        if (preg_match(":<td>(\d+) min</td>:i", $parseblock, $regs))
		{
            $this->addItemAttribute('run_time',$regs[1]);
        }
        
        // Ratio of the picture - OK
        if (preg_match(":alt=\"Image ratio ([\.\d]*)\" title=\"Image ratio ([\d\.]*)\":i",$parseblock,$regs))
		{
            $this->addItemAttribute('ratio', $regs[1]);
        }
       
		// DVD Region - OK
		if (preg_match(":alt=\"Zone (\d)\" title=\"Zone (\d)\":i",$parseblock,$regs))
		{
			$this->addItemAttribute('dvd_region',$regs[1]);
		}

		// Movie plot
		if (preg_match(":Synopsis</div>[\s\t\n]*<div class=\"dvd_text\">([^<>]+)</div>:sim",$parseblock,$regs))
		{
			$str = preg_replace(":\n:im"," ", replace_newlines($regs[1]));
			$this->addItemAttribute('blurb', $str);
		}

		// Director - OK
		if (preg_match("@R.?alisation</div>\n[\t\s]*<div class=\"dvd_text\"><a class=\"dvd_text\" href=\".*\">(.*)</a>@im",$parseblock,$regs))
		{
			$this->addItemAttribute('director',trim($regs[1]));
		} 

		// Number of disc - OK
		if (preg_match("@title=\"Nombre de disques\" src=\".*\" width=\"\d*\" height=\"\d*\" vspace=\"\d*\"></td>\n[\s\t]*<td>(\d+)</td>@im",$parseblock,$regs))
		{
			$this->addItemAttribute('no_discs',$regs[1]);
		}

		// Subtitles available
		if (preg_match(":title=\"Sous-titres disponibles\"(.*?)</td>:sim",$parseblock,$regs))
		{
			if (preg_match_all(":<small>[\s]*(.*?)[\s]*</small>:sim",$regs[1],$result))
			{
				while(list(,$subtitle) = each($result[1])) {
					if(substr($subtitle,-1) == '.') {
						$subtitle = substr($subtitle, 0, -1);
					}
						
					if(strpos($subtitle, ",")!==FALSE) {
						$this->addItemAttribute('subtitles', explode(",", $subtitle));
					} else {
						$this->addItemAttribute('subtitles', $subtitle);
					}
				}		
			}
		}

		// Lists of actors and actresses - OK
		if (preg_match(":<div class=\"dvd_subtitle\">Avec...</div>[\s\t\n]*<div class.*>(.*)</div>:im",$parseblock,$regs))
		{
			$listing = preg_replace(":(<\/[^<>]*>|Avec...|[\s\t]{2,}):i","",rtrim($regs[0]));
			$listing = preg_replace(":<[\!]?[^<>]*>:i",",",$listing);
			$listing = preg_replace(":, ?,+:i",", ",$listing);
			$listing = preg_replace(":^,+:i","",$listing);

			$this->addItemAttribute('actors',explode(",", $listing));
		}

		// Lists of audio languages 
		if (preg_match(":title=\"Sp.?cifications audio\"(.*?)</table>:sim",$parseblock,$regs))
		{
			if (preg_match_all(":<small>(.*)</small>:im",$regs[1],$result))
			{
				$this->addItemAttribute('audio_lang', $result[1]);
			}
		}
		
		return TRUE;
	}
}
?>