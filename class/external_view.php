<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2017-05-10
 * Modified    : 2017-05-10
 * For LOVD    : 3.0-19
 *
 * Copyright   : 2004-2017 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

class LOVD_ExternalView {
    // This class handles the viewing of data from the external LOVD.

    private function loadJSandCSS ()
    {
        // Load the required JS files and CSS file.
        global $_SETT;

        static $bLoaded = false; // Don't load more than once.

        if (!$bLoaded) {
            // Call the JS loader, that's fetching the JS code from the remote LOVD
            //  site, so that we're always compatible with any remote version.
            print('<SCRIPT type="text/javascript" src="' . lovd_getInstallURL() . 'inc-js.php"> </SCRIPT>' . "\n");

            // Now load the CSS. Valid HTML only allows it in the header, but we
            //  don't have direct control over that.
            // FIXME: This injects lots of LOVD markup directly into the page, which is probably not desired. Work around this?
            // HTML5 has a way to set the scope of CSS. Check that out later when needed.
            print('
<SCRIPT type="text/javascript">
  // We know we have jQuery because we\'ve just loaded it.
  $("head").append(\'<LINK rel="stylesheet" type="text/css" href="' . $_SETT['LOVD_URL'] . 'styles.css">\');
</SCRIPT>' . "\n\n");

            $bLoaded = true;
        }

        return $bLoaded;
    }





    protected function viewData ($aViewListSettings = array())
    {
        // The main function to load the external View List. Builds the needed
        //  environment, and sends the request to the internal system.
        global $_SETT;

        if (!isset($aViewListSettings) || !is_array($aViewListSettings)) {
            return false;
        }

        // Load the JS, and CSS, this function makes sure it's done just once.
        $this->loadJSandCSS();

        // Print form; required for sorting and searching.
        // Because we don't want the form to submit itself while we are waiting for the Ajax response, we need to kill the native submit() functionality.
        print('<FORM action="#" method="get" id="viewlistForm_' . $aViewListSettings['viewlistid'] . '" style="margin : 0px;" onsubmit="return false;">' . "\n" .
              '  <INPUT type="hidden" name="viewlistid" value="' . $aViewListSettings['viewlistid'] . '">' . "\n" .
              '  <INPUT type="hidden" name="object" value="' . $aViewListSettings['object'] . '">' . "\n" .
              (!isset($aViewListSettings['object_id'])? '' :
                  '  <INPUT type="hidden" name="object_id" value="' . $aViewListSettings['object_id'] . '">' . "\n") . // The ID of the gene for VOT viewLists, the ID of the disease for phenotype viewLists, the object list for custom viewLists.
              (!isset($aViewListSettings['id'])? '' :
                  '  <INPUT type="hidden" name="id" value="' . $aViewListSettings['id'] . '">' . "\n") . // The ID of the VOG for VOT viewLists, the ID of the individual for phenotype viewLists, the (optional) gene for custom viewLists.
              '  <INPUT type="hidden" name="order" value=",">' . "\n");

        // Skipping (permanently hiding) columns.
        foreach ($aViewListSettings['cols_to_skip'] as $sCol) {
            print('  <INPUT type="hidden" name="skip[' . $sCol . ']" value="1">' . "\n");
        }

        foreach ($aViewListSettings['search'] as $key => $val) {
            print('  <INPUT type="hidden" name="search_' . $key . '" value="' . htmlspecialchars($val) . '">' . "\n");
        }

        // These contents will be replaced by Ajax.
        print("\n" .
              '  <DIV class="LOVD" id="viewlistDiv_' . $aViewListSettings['viewlistid'] . '"><IMG src="gfx/lovd_loading.gif" alt="Loading..."></DIV></FORM><BR>' .
              "\n\n" .
              '<SCRIPT type="text/javascript">' . "\n" .
              '  check_list[\'' . $aViewListSettings['viewlistid'] . '\'] = [];' . "\n" .
              '  lovd_AJAX_viewListSubmit(\'' . $aViewListSettings['viewlistid'] . '\');' . "\n" .
              '</SCRIPT>' . "\n\n");
    }





    public function viewFullData ($sGene, $nTranscriptID)
    {
        // Show the "full data view" of a certain gene with a certain
        //  transcript (its internal ID in that LOVD).

        if (!isset($sGene) || !isset($nTranscriptID)) {
            return false;
        }

        // We won't know if the gene and transcript exist; if not, simply no
        //  data will be shown.

        $aViewListSettings = array(
            'viewlistid' => 'CustomVL_VIEW',
            'object' => 'Custom_ViewList',
            'object_id' => 'VariantOnTranscript,VariantOnGenome,Screening,Individual',
            'cols_to_skip' => array('chromosome'),
            'id' => $sGene,
            'search' => array(
                'transcriptid' => $nTranscriptID,
            ),
        );

        return $this->viewData($aViewListSettings);
    }





    public function viewIndividuals ()
    {
        // Show the individuals.

        $aViewListSettings = array(
            'viewlistid' => 'Individuals',
            'object' => 'Individual',
            'cols_to_skip' => array(),
            'search' => array(),
        );

        return $this->viewData($aViewListSettings);
    }





    public function viewPhenotypes ($nDiseaseID)
    {
        // Show the phenotypes of a certain disease
        //  (its internal ID in that LOVD).

        if (!isset($nDiseaseID)) {
            return false;
        }

        // We won't know if the disease exists; if not, simply no data will be
        //  shown.

        $aViewListSettings = array(
            'viewlistid' => 'viewlistForm_Phenotypes_for_Disease_' . $nDiseaseID,
            'object' => 'Phenotype',
            'object_id' => $nDiseaseID,
            'cols_to_skip' => array('diseaseid', 'individualid'),
            'search' => array(
                'diseaseid' => $nDiseaseID,
            ),
        );

        return $this->viewData($aViewListSettings);
    }
}
?>