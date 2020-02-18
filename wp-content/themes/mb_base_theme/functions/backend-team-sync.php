<?php

// UNTESTED AND TOO RAW TO THINK ABOUT YET !
// TODO loads

if (is_admin()) {
    add_submenu_page('tools.php', 'Manage Team', 'Manage Team via CSV', is_super_admin(), 'teamsync-tool', 'show_team_sync_form');
    add_submenu_page('tools.php', 'Export Team', 'Export Team to CSV', is_super_admin(), 'teamexport-tool', 'show_team_export_form');
}

function show_team_export_form(){
    ?>

    <div class="wrap">
        <h2>
            <?php echo 'Export team to CSV file'; ?>
        </h2>
        <?php
    
        if (isset($_POST['export-syncTeam-flag'])) {
                mb_export_team_to_csv($_POST['myTeams']);
        }
    
        ?>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('is-teamsync-export-nonceField', '_wpnonce-is-teamsync-export-page');?>
            <table class="form-table">
    
                <tr valign="top">
                    <th scope="row"><label for="myTeams">
                            <?php _e('Select Team', 'team-sync-from-csv');?></label></th>
                    <td>
                        <select id="myTeams" name="myTeams" value="selectedValue" class="all-options">
                            <?php
    
        $all_teams = get_posts(['numberofposts' => -1, 'post_type' => 'wc_memberships_team', 'post_status' => 'publish']);
    
        foreach ($all_teams as $count=>$t) {
            $t = wc_memberships_for_teams_get_team($t);
            $owner = $t->get_owner();
            $owner_email = isset($owner->data->user_email) ? $owner->data->user_email : "not set";
            $used_seats = $t->get_used_seat_count();
            $max_seats = $t->get_seat_count();
            $max_seats = $max_seats == 0 ? "unlimited" : $max_seats;
            $list_text = "\"" . $t->get_name() . "\"" .
                " ["
                . $owner_email
                . "] ("
                . $used_seats
                . " of "
                . $max_seats
                . " seats used)";
    
    //echo $list_text;
    
            ?><option value="<?php echo $t->get_id();?>"
            <?php if ($count==0){echo " selected";} ?>
            >
            <?php echo $list_text;?></option><?php }?>
    
                        </select>
                        <!-- <span class="description">
                               <?php echo 'Select the team to work with'; ?></span> -->
                    </td>
                </tr>
                <input type='hidden' name='export-syncTeam-flag' />
                </table>
<div id="subbieEXPORT">
        <p class="submit">
            <input id="goEXPORTButton" type="submit" class="button-primary" value="Export" />
        </p>
        </div>
    </form>
    <?php
   
}


function show_team_sync_form()
{

    ?>

<div class="wrap">
    <h2>
        <?php echo 'Manage team using a CSV file'; ?>
    </h2>
    <?php

    if (isset($_POST['backend-syncTeam-flag'])) {

        $ret = process_uploaded_File();
    }

    ?>
    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field('is-teamsync-import-nonceField', '_wpnonce-is-teamsync-import-page');?>
        <table class="form-table">

            <tr valign="top">
                <th scope="row"><label for="myTeams">
                        <?php _e('Select Team', 'team-sync-from-csv');?></label></th>
                <td>
                    <select id="myTeams" name="myTeams" value="selectedValue" class="all-options">
                        <?php

    $all_teams = get_posts(['numberofposts' => -1, 'post_type' => 'wc_memberships_team', 'post_status' => 'publish']);

    foreach ($all_teams as $count=>$t) {
        $t = wc_memberships_for_teams_get_team($t);
        $owner = $t->get_owner();
        $owner_email = isset($owner->data->user_email) ? $owner->data->user_email : "not set";
        $used_seats = $t->get_used_seat_count();
        $max_seats = $t->get_seat_count();
        $max_seats = $max_seats == 0 ? "unlimited" : $max_seats;
        $list_text = "\"" . $t->get_name() . "\"" .
            " ["
            . $owner_email
            . "] ("
            . $used_seats
            . " of "
            . $max_seats
            . " seats used)";

//echo $list_text;

        ?><option value="<?php echo $t->get_id();?>"
        <?php if ($count==0){echo " selected";} ?>
        >
        <?php echo $list_text;?></option><?php }?>

                    </select>
                    <!-- <span class="description">
                           <?php echo 'Select the team to work with'; ?></span> -->
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="users_csv">
                        <?php _e('CSV file', 'backend-team-sync-from-csv');?></label></th>
                <td>
                    <input type="file" id="backend-team-sync-from-csv" name="backend-team-sync-from-csv" value="" class="all-options" /><br />
                    <input type='hidden' name='backend-syncTeam-flag' />
                    <span class="description">
                        <br />
                    </span>
                </td>
            </tr>

            <tr id="tSyncRow" valign="top">
                <!-- <th style="width:100%;" scope="row"> -->
                <td colspan="5">
                <div style="display:inline-block;height:auto;">
                        <div id="currentTeamHeading"><?php echo 'Currently selected Team: '; ?>
                        </div>
                        <div id="currentTeam">None</div>
                    </div>
                    </td>


                <!-- </th> -->

            </tr>

<tr>
<th scope="row"><label for="users_csv">
                        UPLOAD OPTION</label></th>

                        <td colspan="5">
<select id="myFileOption" name="myFileOption" value="selectedValue" class="all-options">

<option value="sync">Sync</option>
<option value="add">Add new</option>
<option value="remove">Remove</option>
                    </select>
                    <span class="description">
                           Select the action for your file upload (VERY IMPORTANT)</span>
</td></tr>
<tr id="tOptionsRow" valign="top">
                <!-- <th style="width:100%;" scope="row"> -->
                <td colspan="5">
                <div style="display:inline-block;height:auto;">
                        <div id="currentOptionHeader"><?php echo 'Currently selected Option: '; ?>
                        </div>
                        <div id="currentOptionChoice">Sync</div>
                                            </div>
                                            <div id="currentOptionDescription"></div>
                    </td>


                <!-- </th> -->

            </tr>


        </table>
<div id="subbie">
        <div> No further warnings - clicking this button starts the process immediately!</div>
        <p class="submit">
            <input id="goButton" type="submit" class="button-primary" value="Go - Sync" />
        </p>
        </div>
    </form>
    <?php
}

function process_uploaded_File()
{

    $error_log_msg = '';

    if (file_exists($error_log_file)) {
        $error_log_msg = sprintf(__(', please <a href="%s">check the error log</a>', 'backend-team-sync-from-csv'), $error_log_url);
    }

    $errors = new WP_Error();
    $results = [];

    $uFileName = "/" . uniqid() . '_backendteamSyncErrors.txt';
    $error_logFile = get_stylesheet_directory() . $uFileName;
    $error_logFile_url = get_stylesheet_directory_uri() . $uFileName;

    $uploadOk = 1;

    if ($_FILES["backend-team-sync-from-csv"]["size"] > 250000) {
        $errors->add('filetooBig', 'That file is too big sorry !');
        $uploadOk = 0;
    }

    // validate the filename and size
    if (empty($_FILES['backend-team-sync-from-csv']['tmp_name'])) {
        $errors->add('noFile', 'The filename is empty - please select a file first');
        $uploadOk = 0;

    }

    if ($uploadOk) {

        $isOK = true;
        // POST upload sanity check
        // check if it's a csv file
        // not a csv
        if (substr($_FILES['backend-team-sync-from-csv']['name'], -4) != '.csv') {
            $errors->add('notCSV', 'Your filename must end with ".csv"');
            $isOK = false;
        }

        // check header
        // check data first row

        if (!isSyncfileValid($_FILES['backend-team-sync-from-csv']['tmp_name'], $errors)) {
            $isOK = false;
        }

        $team = wc_memberships_for_teams_get_team($_POST['myTeams']);
        if (!$team) {
            $errors->add('notTEAM', 'Your team choice generated an error: contact support');
            $isOK = false;

        }

        
        if (!isset($_POST['myFileOption'])){

            $errors->add('notPROCESS', 'You need to make a process choice SYNC, ADD, or REMOVE');
            $isOK = false;
        }

        $processType= $_POST['myFileOption'];

        if ($isOK) {

            

            //if (!empty($_FILES['syncTeamFile']['tmp_name'])) {
            // Setup settings variables
            $filename = $_FILES['backend-team-sync-from-csv']['tmp_name'];
if ($processType=="Export"){
    mb_export_team_to_csv($team);
    exit;
}


            $results = do_teamSync($filename, $team, $error_logFile, $processType);
            if (count($results) > 1) {

                echo '<ul class="f_alert f_red">';
                foreach ($results as $k => $r) {
                    if ($k > 0) {
                        echo '<li>' . $r . '</li>';
                    } else {
                        echo '<li>Latest log file is here <a href="' . $error_logFile_url . '">to view</a></li>';
                    }
                }
                echo '</ul>';

            } else {
                // we've returned the process details in the first element of array
                echo '<ul class="f_alert f_green">';
                echo '<li>' . $results[0] . '</li>';
                echo '<li>The Sync log is here <a href="' . $error_logFile_url . '"> (will be deleted at midnight so please save if needed)</a></li>';
                echo '</ul>';
            }
        }
    }
}