$(document).ready(function () {

    // enable the team sync current selection 

    $('#tSyncRow').show();
    $('#tOptionsRow').show();
    $('#currentTeam').text($("#myTeams option:selected").text());
    showOptionsText();

}); // end doc ready


$('#myTeams').change(function () {

    $('#currentTeam').text($("#myTeams option:selected").text());
});

$('#myFileOption').change(function () {

    selText = $("#myFileOption option:selected").text();
    $('#currentOptionChoice').text(selText);
    showOptionsText()
    $('#goButton').val("Go - "+selText);
});




function showOptionsText() {
    var descriptions = [
        [
            "This option makes the team look like the csv contents.<br>All new people are added and any not in the csv are removed.<br>Careful - this is not reversible!"
        ], [
            "This option just adds any new users from the csv.<br>Newly added users get an invitation email."
        ], [
            "This option just removes the csv users fom the team (re-adding them back in later resends an invitation email)<br> No removal emails are sent to users"
        ]
    ];

    $option = $("#myFileOption option:selected").text();

    if ($option === 'Sync') {
        $o = 0;
    }
    if ($option === 'Add new') {
        $o = 1;
    }
    if ($option === 'Remove') {
        $o = 2;
    }
    
    $('#currentOptionDescription').html(descriptions[$o]);
}
