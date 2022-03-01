<?php
namespace Stanford\DesignatedContact;
use Stanford\DesignatedContact;
/** @var \Stanford\DesignatedContact\DesignatedContact $module */


require APP_PATH_DOCROOT . "ControlCenter/header.php";

function getTitle() {
    return "Designated Contact Report - Projects with no DC selected";
}

function getHeader() {
    return array('Project ID', 'Title', 'Creation Time', 'Status', 'Num of Records');
}

function getData() {

    global $module, $redcap_version, $redcap_base_url;

    $projectUrl = $redcap_base_url . "redcap_v{$redcap_version}/ProjectSetup/index.php?pid=";
    $module->emDebug("project URL: " . $projectUrl);

    $all_projects = array();
    // Get the project with no DC that are either
    $sql = "select rp.project_id, rp.app_title, rp.creation_time,
                    if (rp.status = 0, 'Development', 'Production') as status,
                    count(rrl.record) as num_records
                from redcap_projects rp
                        left join redcap_record_list rrl on rp.project_id = rrl.project_id
                where rp.completed_time is null
                and rp.date_deleted is null
                and rp.status <> 2
                and rp.project_id not in (
                        select project_id from designated_contact_selected
                )
                group by rp.project_id, rp.status
            order by rp.project_id";
    $q = db_query($sql);
    while ($results = db_fetch_assoc($q)) {
        $project_id = "<a style='color: blue' href='" . $projectUrl . $results['project_id'] . "'>" . $results['project_id'] . "</a>";
        $results['project_id'] = $project_id;
        $all_projects[] = $results;
    }
    $module->emDebug("These are the projects without DC: " . json_encode($all_projects));

    return $all_projects;

}

function getTableID() {
    return 'noDCProjects';
}

function renderTable()
{
    $grid = "";

    //Render table
    $grid .= '<div>';

    $grid .= '<table class="table cell-border" id="' . getTableID() . '">';
    if (!empty($title)) {
        $grid .= "<caption>" . getTitle(). "</caption>";
    }
    $grid .= renderHeaderRow(getHeader());
    $grid .= renderTableRows(getData());
    $grid .= '</table>';

    $grid .= '</div><br><br>';

    return $grid;
}

function renderHeaderRow($header)
{
    $row = '<thead><tr>';
    $num_cols = count($header);
    if ($num_cols >= 10) {
        $font_size = 11;
    } else {
        $font_size = 12;
    }

    foreach ($header as $col_key => $this_col) {
        $row .= '<th scope="col" style="color: black; background: lightgrey; font-size:' . $font_size . 'px !important;"><b>' . $this_col . '</b>';
        $row .= '<i class="fa float-right" aria-hidden="true"></i>';
        $row .= '</th>';
    }

    $row .= '</tr></thead>';

    return $row;
}

function renderTableRows($data)
    {
        global $module;
        $rows = '<tbody>';

        foreach ($data as $row_key => $this_row) {
            $rows .= '<tr>';

            foreach($this_row as $rowKey => $rowValue) {
                $rows .= '<td>' . $rowValue . '</td>';
            }

            // End row
            $rows .= '</tr>';
        }

        $rows .= '</tbody>';

        return $rows;
    }


?>

<html>
    <header>
        <style>
            h4 {text-align: center; margin-top: 40px; margin-bottom: 20px; color: maroon}
        </style>
    </header>
    <body>
        <div class="container">
            <h4><?php echo getTitle(); ?></h4>
            <br>

                <?php echo renderTable(); ?>

        </div>  <!-- END CONTAINER -->
    </body>
</html>
<script>

    $(document).ready(function() {

        var tables = document.getElementsByClassName("table");
        for (var ncnt = 0; ncnt < tables.length; ncnt++) {
            var tableElement = $('#' + tables[ncnt].id);

            tableElement.DataTable({
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
                dom: 'Bftlp',
                buttons: {
                    name: 'primary',
                    buttons: ['copy', 'excel', 'pdf',
                        {
                            extend: 'print',
                            customize: function (win) {
                                $(win.document.body)
                                    .css('font-size', '12pt');
                                $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                            }
                        }
                    ]
                }
            });

            $(".dt-buttons").css("left", 30);
            $(".dt-buttons").addClass('hidden-print');
            $(".dataTables_filter").addClass('hidden-print');
            $(".dataTables_length").addClass('hidden-print');
        }
    });

</script>
