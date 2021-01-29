
<script>

    $(function() {

        //Retrieve the list of projects this user is designated contact and make an array
        if (projList !== null) {
            var projList = '<?php echo json_encode($projects); ?>';
            var projectObject = JSON.parse(projList.valueOf());
            var projectList = Object.keys(projectObject);

            // Find each project and insert the Designated Contact image
            var nodes = document.querySelectorAll("a.aGrid");

            nodes.forEach(function (node) {

                // Find the project ID from the URL
                var url = node.getAttribute("href");
                var index = url.indexOf("pid=");
                var project_id = url.substring(index + 4, url.length);

                // See if this project ID is in our list of Designated Contact projects
                if (projectList.includes(project_id)) {

                    // Add the icon before the project link
                    var newIcon = document.createElement("span");
                    newIcon.classList.add("fas");
                    newIcon.classList.add("fa-address-book");
                    newIcon.setAttribute("title", "You are the Designated Contact for this project");
                    newIcon.setAttribute("style", "margin-right:7px");
                    node.prepend(newIcon);

                    // Move up the DOM and remove the padding-left 10 px instead of 30px
                    var parent = node.parentNode;
                    if (parent != null) {
                        parent.parentNode.setAttribute("style", "padding-left: 10px;");
                    }
                }
            });
        }
    });

</script>

