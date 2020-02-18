<?php

// csv reading and syncing
require_once get_stylesheet_directory() . '/readCSV.php';

//add_action('wc_memberships_for_teams_before_my_team_add_member', 'extraTeamCode');
add_action('wc_memberships_for_teams_before_my_team_members', 'extraTeamCode');

add_action('wc_memberships_for_teams_before_my_team_add_member', 'extraTeamHeader');

//add_action('init', 'process_TeamSync', 20);
function extraTeamHeader($t)
{
    echo_team_header($t, true);
}

function extraTeamCode($t)
{

// export flag if present?
    if (isset($_POST['exportTeam'])) {
        // get the team id from the url
        $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri_segments = explode('/', $uri_path);
        $team_id = $uri_segments[3];
        mb_export_team_to_csv($team_id);
        die(); // in case !
    }

    /////////////// form validation
    if (isset($_POST['syncTeam-flag'])) {

        if (check_admin_referer('syncTeam-import', '_team_sync_nonce')) {

            // TODO these log files need deleting every midnight - else they will clog us up !
            // once viewed or downloaded they are no longer available anyway unless users saves the url

            $errors = new WP_Error();
            $results = [];

            $uFileName = "/" . uniqid() . '_teamSyncErrors.txt';
            $error_logFile = get_stylesheet_directory() . $uFileName;
            $error_logFile_url = get_stylesheet_directory_uri() . $uFileName;

            $uploadOk = 1;

            if ($_FILES["syncTeamFile"]["size"] > 250000) {
                $errors->add('filetooBig', 'That file is too big sorry !');
                $uploadOk = 0;
            }

            // validate the filename and size
            if (empty($_FILES['syncTeamFile']['tmp_name'])) {
                $errors->add('noFile', 'The filename is empty - please select a file first');
                $uploadOk = 0;

            }

            if ($uploadOk) {

                $isOK = true;
                // POST upload sanity check
                // check if it's a csv file
                // not a csv
                if (substr($_FILES['syncTeamFile']['name'], -4) != '.csv') {
                    $errors->add('notCSV', 'Your filename must end with ".csv"');
                    $isOK = false;
                }

                // check header
                // check data first row

                if (!isSyncfileValid($_FILES['syncTeamFile']['tmp_name'], $errors)) {
                    $isOK = false;
                }

                if ($isOK) {

                    //if (!empty($_FILES['syncTeamFile']['tmp_name'])) {
                    // Setup settings variables
                    $filename = $_FILES['syncTeamFile']['tmp_name'];
                    $results = do_teamSync($filename, $t, $error_logFile, $_POST['myFileOptions']);
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
                        echo '<li>The last team Sync log is here <a href="' . $error_logFile_url . '">to view - (will be deleted at midnight so please save if needed)</a></li>';
                        echo '</ul>';
                    }

                }

            }
        }
    }
    //  form

    echo_team_header($t, false);

    echo "<div id='export_team'>";
    echo "<h5>Export Team to CSV file</h5>";
    echo "<form id='exportTeam' method='POST'>";
    //wp_nonce_field('exportTeam', '_team_export_nonce');

    echo "<input type='hidden' name='exportTeam'>";
    //echo $t->get_id();

    echo "
        <input type='submit' class='csvButton' value='Export' />
        </form><br />";

    echo "</div><br>";

    echo "<div id='team_sync'>";
    echo "<h5>Manage Team via CSV file</h5>";

    if (isset($errors) && $errors->get_error_code()):
        echo '<ul class="f_alert f_red">';
        foreach ($errors->errors as $error) {
            echo '<li>' . $error[0] . '</li>';
        }
        echo '</ul>';
    endif;

    echo "<form id='syncTeam' method='POST' enctype='multipart/form-data'>";
    wp_nonce_field('syncTeam-import', '_team_sync_nonce');

    echo "<input type='hidden' name='syncTeam' value='";
    echo $t->get_id();
    echo "'>

        <div id='front-team-choice' class='form-fields'>
        <input type='hidden' name='syncTeam-flag' />
        <input type='file' id='syncTeamFile' name='syncTeamFile' class='syncTc' /><br />
        <div id='syncDescription'>Choose 'sync' to match csv with team members, 'Add new' to just add members from csv, or 'Remove' to remove members in the csv from the team.</div>
        <select id='myFileOptions' name='myFileOptions' class='syncTc'>
        <option value=\"Sync\">Sync</option>
        <option value=\"Add new\">Add new</option>
        <option value=\"Remove\">Remove</option>
        </select>

        <br />
        </div>";

    echo "
        <input type='submit' class='csvButton' value='Start' />
        </form><br />";

    echo "</div><br>";

}

function echo_team_header($t, $add)
{

    echo "<a href='" . esc_url(wc_get_account_endpoint_url("teams")) . "'>Back to Teams dashboard</a>";
    echo "<h3>";

    if ($add) {
        echo "Manually add members to team";
    } else {
        echo "Members for team";
    }

    echo ": " . $t->get_name() . "</h3>";
    $totSeats = $t->get_seat_count() == 0 ? "unlimited" : $t->get_seat_count();

    printf('This team has <strong>%s seats</strong>.', $totSeats);
    echo "<br><br>";
}

function isSyncfileValid($f, &$e)
{

    $file_handle = @fopen($f, 'r');
    if ($file_handle) {
        $csv_reader = new ReadCSV($file_handle, IS_IU_CSV_DELIMITER, "\xEF\xBB\xBF"); // Skip any UTF-8 byte order mark.
        // get second row - look for header column names of email and password
        $line = $csv_reader->get_row();
        if ($line == null) {
            $e->add('readerror', 'We had a read error on row 1');
            return false;
        }

        // $line = $csv_reader->get_row();
        // if ($line == null) {
        //     $e->add('readerror', 'We had read error on row 2');
        //     return false;
        // }

        // if (!isset($line[0])) {
        //     $e->add('headerError', 'Your header row is short of fields');
        //     return false;
        // }

        // read one more line to see if we have data
        $line = $csv_reader->get_row();

        if (strtolower(trim($line[0])) != "email") {
            // || strtolower(trim($line[1])) != "firstname"
            // || strtolower(trim($line[2])) != "lastname"
            // || strtolower(trim($line[3])) != "password") {
            $e->add('headerError', 'Your column headings in ROW2 are not right');
            return false;
        }

        // read one more line to see if we have data
        $line = $csv_reader->get_row();

        if ($line == null) {
            $e->add('readerror', 'We had a read error on row 2');
            return false;
        }

    } else {
        $e->add('readerror', 'We can\'t read that file');
    }
    fclose($file_handle);
    return true;

}

function do_teamSync($f, $t, $l, $p) // $f is the file, $t is team id and $l is the log text file - $p is process types

{
    switch ($p) {
        case 'Remove':
            $pType = "r";
            break;
        case 'Sync':
            $pType = "s";
            break;
        default:
        case 'Add new':
            $pType = "a";
            break;
    }

    $team_id = $t->get_id();
    $validCounter = 0;
    $invalidCounter = 0;
    $totalCounter = 0;
    $duplicateCounter = 0;
// arrays for incoming and current email addresses
    $incoming = [];
    $current = [];

    $file_handle = @fopen($f, 'r');
    $csv_reader = new ReadCSV($file_handle, IS_IU_CSV_DELIMITER, "\xEF\xBB\xBF"); // Skip any UTF-8 byte order mark.

// read two lines to ditch header and title
    $line = $csv_reader->get_row();
    $line = $csv_reader->get_row();

// read entire xls into array
    while (($line = $csv_reader->get_row()) != null) {
        if ($line[0] != "") {
            $totalCounter++;
            if (filter_var($line[0], FILTER_VALIDATE_EMAIL)) {
                // is it already in incoming?
                if (in_array(trim(strtolower($line[0])), $incoming)) {
                    $duplicateCounter++;
                } else {

                    $incoming[] = trim(strtolower($line[0]));
                    $validCounter++;
                }
            } else {
                $invalidCounter++;
            }
        }
    }

    fclose($file_handle);

// now read all current tem members into current array
    //$members = wc_memberships_for_teams_get_team_members($_POST['syncTeam'],  [ 'role' => 'member' ] );
    $members = wc_memberships_for_teams_get_team_members($team_id);
    //$members = wc_memberships_for_teams_get_team_members($_POST['syncTeam']);
    foreach ($members as $m) {
        $user = $m->get_user();
        $r = $m->get_role();
        $current[] = [
            'email' => get_userdata($user->ID)->user_email,
            'status' => $r,
            'id' => $user->ID,
        ];
        //$current[]=$m->get_user_email();
    }

// now add all invitations
    // now read all current tem members into current array
    //$members = wc_memberships_for_teams_get_invitations($_POST['syncTeam'],  [ 'status' => 'pending', 'role' => 'member' ] );
    //$members = wc_memberships_for_teams_get_invitations($_POST['syncTeam'],['status'=>'wcmti-pending']);
    //$members = wc_memberships_for_teams_get_invitations($_POST['syncTeam']);
    $members = wc_memberships_for_teams_get_invitations($team_id);
    foreach ($members as $m) {
        $current[] = [
            'email' => $m->get_email(),
            'status' => $m->get_status(),
        ];

    }

// create process log
    $n = strtotime("now");
    $res = [];
    $mex = wp_get_current_user();
    $current_user = get_userdata($mex->ID)->user_email;
    $today = date('d/m/y H:i:s', $n);
    $team_id = $t->get_id();
// open text log file
    $log = fopen($l, "a");
    $res[] = 'PLACEHOLDER _ UNUSED';
    doLog($res, $log, "Sync started at " . date('d/m/y H:i:s', $n));

// return one element with success message, or mutiple with first unused as errors

// remove existing members and invites if not in csv
    // oo-er this feels dangerous - but can be rerun to force ok again ( with fresh invites to members)

    if ($pType == "s") { // ONLUY DO THIS IF SYNCING
        //foreach current- if is in incoming
        // yes - log already present
        // no - delete or uninvite - and log
        foreach ($current as $c) {
            {
                // if email NOT present in spreadsheet - remove member/invite
                if (!in_array($c['email'], $incoming)) {
                    if (strtolower($c['status'] == "owner") || strtolower($c['status'] == "manager")) {
                        doLog($res, $log, 'Existing =' . $c['email'] . ' - user is owner or manager so left alone');
                    } else {
                        if ($c['status'] == "pending") {
                            // invitation

                            try {
                                $inv = wc_memberships_for_teams_get_invitation($team_id, $c['email'])->cancel();
                                doLog($res, $log, 'Existing =' . $c['email'] . ' invitation cancelled');
                            } catch (Exception $e) {
                                doLog($res, $log, 'Existing =' . $c['email'] . ' (attempting invitation cancellation) error: ' . $e->getMessage());
                            }

                        } else {
                            try {
                                // member
                                //$t->remove_member($c['id'], false, 'Membership cancelled by '.$current_user.' during team sync refresh on '.$today);
                                $t->remove_member($c['id']);
                                doLog($res, $log, 'Existing =' . $c['email'] . ' member removed');
                            } catch (Exception $e) {
                                doLog($res, $log, 'Existing =' . $c['email'] . ' (attempting member removal) error: ' . $e->getMessage());
                            }
                        }
                    }
                }}
        }
    }
    if ($pType == "r") { // ONLUY DO THIS IF REMOVING
        //foreach current- if is in incoming
        // yes - log already present
        // no - delete or uninvite - and log
        foreach ($current as $c) {
            {
                // if email NOT present in spreadsheet - remove member/invite
                if (in_array($c['email'], $incoming)) {
                    if (strtolower($c['status'] == "owner") || strtolower($c['status'] == "manager")) {
                        doLog($res, $log, 'Existing =' . $c['email'] . ' - user is owner or manager so left alone');
                    } else {
                        if ($c['status'] == "pending") {
                            // invitation

                            try {
                                $inv = wc_memberships_for_teams_get_invitation($team_id, $c['email'])->cancel();
                                doLog($res, $log, 'Existing =' . $c['email'] . ' invitation cancelled');
                            } catch (Exception $e) {
                                doLog($res, $log, 'Existing =' . $c['email'] . ' (attempting invitation cancellation) error: ' . $e->getMessage());
                            }

                        } else {
                            try {
                                // member
                                //$t->remove_member($c['id'], false, 'Membership cancelled by '.$current_user.' during team sync refresh on '.$today);
                                $t->remove_member($c['id']);
                                doLog($res, $log, 'Existing =' . $c['email'] . ' member removed');
                            } catch (Exception $e) {
                                doLog($res, $log, 'Existing =' . $c['email'] . ' (attempting member removal) error: ' . $e->getMessage());
                            }
                        }
                    }
                }}
        }

    } // end of removal process

    if ($pType == "s" || $pType == "a") { // ONLUY DO THIS IF SYNCING OR ADDING

// create new invites
        // foreach incoming - if is in current
        foreach ($incoming as $inc) {
            if (mb_searchForEmail($inc, $current)) {
                doLog($res, $log, 'Incoming=' . $inc . ' is already a member or pending - skipped');
            } else {

                // create invitation
                try {
                    $invit = $t->invite($inc);
                    //$invit = wc_memberships_for_teams_create_invitation(['team_id' => $team_id, 'email' => $inc]);
                    doLog($res, $log, 'Incoming=' . $inc . ' invitation created');
                } catch (Exception $e) {
                    doLog($res, $log, 'Incoming=' . $inc . ' error: ' . $e->getMessage());
                    if (5 === $e->getCode()) // no more seats{
                    {
                        doLog($res, $log, ""); // blank line into log
                        doLog($res, $log, 'No more free seats - import abandoned at this point');
                        break;
                    }

                }

            }
        }
    }
    // end of process
    fclose($log);

    return $res;

}

function doLog(&$r, $l, $txt)
{
    $r[] = $txt;
    $write = fputs($l, $txt . "\n");
}

function mb_searchForEmail($email, $array)
{

    foreach ($array as $key => $val) {
        if ($val['email'] === $email) {
            return true;
        }
    }

    return false;
}
