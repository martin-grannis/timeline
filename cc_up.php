<?php
session_start();
$_POST    = $_SESSION;
$_SESSION = [];
session_destroy();
?>

<!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>CC Component reader</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Testing CDN</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/foundation-sites@6.5.0-rc.3/dist/css/foundation.min.css"
          integrity="sha256-b2khkeAav/7kTh0Bs5h1Xw1kqGL56SziJ5zk6bEvnAw= sha384-7nP0F9FVCI9Qg1SfsjHWQd+4ksCAxlF5pibRyPGxwn7NJpu1XuSaOoMh8JHIDSdk sha512-Rcgo7Zj9clxZoGtt4CBj1aEtCL9gBd64nYl3hkKEuWDwtK7hKY6c4D5vL4njDseuz31u1WWSM42SbvYe/3CZYQ=="
          crossorigin="anonymous">

    <link rel="stylesheet" href="cc_up.css">
</head>
<body>

<div class="grid-container">

    <div class="grid-x">

        <h1><a>CC Component reader</a></h1>

    </div>

    <form name="upload" method="post" action="cc_up_process.php" enctype="multipart/form-data">

        <div class="grid-x  grid-padding-x">
            <div class="cell medium-6 large-6">
                <div class="form-floating-label has-value">
                    <input class="button" type="file" name="fileToUpload" id="fileToUpload">
                </div>
            </div>
            <div id="buttonage" class="cell medium-6 large-6">
                <div class="form-floating-label has-value">
                    <input class="button success" id="saveForm" type="submit" name="submit" value="Analyse"/>
                </div>
                <div class="form-floating-label has-value">
                    <input class="button alert" id="downloadForm" type="submit" name="downloadForm" value="Download New"/>
                </div>
            </div>
        </div>

        <div class="grid-x  grid-padding-x">
            <div class="cell medium-6 large-6">
                <div class="form-floating-label has-value">
                    <input type="text" name="uuid"
                           value="<?php if ( isset( $_POST['uuid'] ) ) {
						       echo trim( $_POST['uuid'] );
					       } ?>">
                    <label for="uuid">UUID</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="buildNumber"
                           value="<?php if ( isset( $_POST['buildNumber'] ) ) {
						       echo trim( $_POST['buildNumber'] );
					       } ?>">
                    <label for="buildNumber">BuildNumber</label>
                </div>

                <div class="form-floating-label has-value">
                    <textarea rows="4" name="description"><?php if ( isset( $_POST['description'] ) ) {
	                           echo trim( $_POST['description'] );
                           } ?></textarea>
                    <label for="description">Description</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="level"
                           value="<?php if ( isset( $_POST['level'] ) ) {
						       echo trim( $_POST['level'] );
					       } ?>">
                    <label for="level">Level</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="name"
                           value="<?php if ( isset( $_POST['name'] ) ) {
						       echo trim( $_POST['name'] );
					       } ?>">
                    <label for="name">Name</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="type"
                           value="<?php if ( isset( $_POST['type'] ) ) {
						       echo trim( $_POST['type'] );
					       } ?>">
                    <label for="type">Type</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="units"
                           value="<?php if ( isset( $_POST['units'] ) ) {
						       echo trim( $_POST['units'] );
					       } ?>">
                    <label for="units">Units</label>
                </div>


            </div>
            <div class="cell medium-6 large-6">
                <div class="form-floating-label has-value">
                    <input type="text" name="appId"
                           value="<?php if ( isset( $_POST['appId'] ) ) {
						       echo trim( $_POST['appId'] );
					       } ?>">
                    <label for="appId">appId</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="appVersion"
                           value="<?php if ( isset( $_POST['appVersion'] ) ) {
						       echo trim( $_POST['appVersion'] );
					       } ?>">
                    <label for="appVersion">appVersion</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="element_name"
                           value="<?php if ( isset( $_POST['element_name'] ) ) {
						       echo trim( $_POST['element_name'] );
					       } ?>">
                    <label for="element_name">Element Name</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="mobile_first"
                           value="<?php if ( isset( $_POST['mobile_first'] ) ) {
						       echo trim( $_POST['mobile_first'] );
					       } ?>">
                    <label for="mobile_first">mobile First</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="user_email"
                           value="<?php if ( isset( $_POST['user_email'] ) ) {
						       echo trim( $_POST['user_email'] );
					       } ?>">
                    <label for="user_email">User Email</label>
                </div>

                <div class="form-floating-label has-value">
                    <input type="text" name="framework"
                           value="<?php if ( isset( $_POST['framework'] ) ) {
						       echo trim( $_POST['framework'] );
					       } ?>">
                    <label for="framework">Framework</label>
                </div>


            </div>
        </div>


        <div class="cell medium-12 large-12">

            <div class="form-floating-label has-value">
                <textarea class="text-area-small" rows="6" name="html_field"
          class="element textarea medium"><?php if ( isset( $_POST['html_field'] ) ) {
		echo trim( $_POST['html_field'] );
	} ?></textarea>
                <label for="html_field">html</label>
            </div>

            <div class="form-floating-label has-value">
            <textarea class="text-area-small" rows="6" name="css_field"
                      class="element textarea medium"><?php if ( isset( $_POST['css_field'] ) ) {
		            echo trim( $_POST['css_field'] );
	            } ?></textarea>
                <label for="css_field">css</label>
            </div>


	        <?php if ( isset( $_POST['resource_url'] ) ) {
		        echo '<a class-"res_link" href="'.trim( $_POST['resource_url'].'" target="_blank">Resources are present here</a>' );
	        } ?>


            <div class="form-floating-label has-value">
                <input type="text" name="resources"
                       value="<?php if ( isset( $_POST['resources'] ) ) {
					       echo trim( $_POST['resources'] );
				       } ?>">
                <label for="resources">Resources</label>
            </div>


        </div>


    </form>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/what-input/5.1.2/what-input.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/foundation-sites@6.5.0-rc.3/dist/js/foundation.min.js"
            integrity="sha256-l1HhyJ0nfWQPdwsVJLNq8HfZNb3i1R9M82YrqVPzoJ4= sha384-NH8utV74bU+noXiJDlEMZuBe34Bw/X66sw22frHkgIs8R1QKWc8ckxYB4DheLjH4 sha512-JMs3Y+JjY+DhwVOPeJhkLM/0FeK9ANxvuYiHGpGp7Q2kMlmNEN/2v6TkrXdirxqB3DHxPlQ8iMxvb/eSPCF5CA=="
            crossorigin="anonymous"></script>
    <script src="cc_upd_mb.js"></script>

</div>
</div>

</body>
</html>