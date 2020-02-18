<?php

function mb_export_team_to_csv($team_id)
{
     
    //if (check_admin_referer('syncTeam-import', '_team_sync_nonce')) {
        // was $_POST['syncTeam']
        $team = wc_memberships_for_teams_get_team($team_id);
        $n = strtotime("now");
        $mex = wp_get_current_user();
        $current_user = get_userdata($mex->ID)->user_email;
        $today = date('d/m/y H:i:s', $n);

        //// refactor this as its duplicate of sync code below
        $members = wc_memberships_for_teams_get_team_members($team);
        foreach ($members as $m) {
            $user = $m->get_user();
            $r = $m->get_role();
            $current[] = [
                'email' => get_userdata($user->ID)->user_email,
                'status' => $r,
                //'id' => $user->ID,
            ];
        }

        $members = wc_memberships_for_teams_get_invitations($team);
        foreach ($members as $m) {
            $current[] = [
                'email' => $m->get_email(),
                'status' => $m->get_status(),
            ];

        }

        // create tmp file
        //$out = fopen('php://output', 'w');
        $tmpfname = tempnam("/tmp", "FOO");
        $out = fopen($tmpfname, 'w');

        fputcsv($out, [
            "Export of " . $team->get_name(). " Team members at " . $today . " - run by " . $current_user,
            "",
        ]);

        fputcsv($out, [
            "email", "",
        ]
        );

        foreach ($current as $c) {
            fputcsv($out, [$c['email'], $c['status']]);
        }

        fclose($out);

        
        while (ob_get_level()){
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename=expTeamMembers.csv');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        //header('Content-Type: application/csv');
        //header("Content-type: application/vnd.ms-excel");
        
        header("Content-Length: " . filesize($tmpfname));

        readfile($tmpfname);
        
//         $fp = fopen($tmpfname, "r");
//         fpassthru($fp);
//         fclose($fp);

// //            fputcsv($out, array('this', 'is some', 'csv "stuff", you know.'));

        unlink($tmpfname);

        die();
        // write each line of $team members for download

    }

//}

//add_action('admin_post_nopriv_export_Team', 'mb_export_team_to_csv');
//add_action('admin_post_export_Team', 'mb_export_team_to_csv');