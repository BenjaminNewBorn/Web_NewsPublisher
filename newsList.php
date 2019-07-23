<?php
session_start();

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

require 'database.php';

if($_SESSION['login'] && isset($_POST['token']) && !empty($_POST['token'])) {
    if(!hash_equals($_SESSION['token'], $_POST['token'])){
        die("Request forgery detected");
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>index</title>
    <link rel="stylesheet" type="text/css" href="newsWeb.css">
</head>
<body>
<h2>Welcome!&nbsp;<?php echo htmlentities($username);?></h2>


<!--
If user has log in, site shows "Logout" button
If user does not log in, site shows "Login" button
-->
<?php if(!$_SESSION['login']) { ?>
<a href="newsUserLogin.php">login</a>
<?php
} else {
?>
<a href="newsUserLogin.php">logout</a>
<?php
}

/*
 * Creation Part: Realize Paging function
*/
//set the number of columns each page
$page_size = 5;
//get the total number of stories
$stmt = $mysqli->prepare("SELECT count(*) FROM stories");
if(!$stmt) {
    printf("Query1 Prep Failed: %s\n", $mysqli->error);
    exit;
}
$stmt->execute();
$stmt->bind_result($num_rows);
$stmt->fetch();
//get total number of pages
$pages = intval($num_rows/$page_size);
if($num_rows%$page_size) {
    $pages++;
}
//set the current page
if(isset($_GET['page'])) {
    $page = intval($_GET['page']);
} else {
    //if not determine the page no, turn to the first page
    $page = 1;
}
//set offset
$offset = $page_size * ($page - 1);
$stmt->close();

//get Story list
$stmt = $mysqli->prepare("SELECT s.id, s.title, u.name, s.update_time, s.visit_count FROM users u, stories s WHERE s.author = u.id ORDER BY s
.update_time DESC LIMIT ?,?");
if(!$stmt) {
    printf("Query2 Prep Failed: %s\n", $mysqli->error);
    exit;
}
$stmt->bind_param("ii", $offset, $page_size);
$stmt->execute();
$stmt->bind_result($story_id, $title, $author, $s_update_time, $visit_count);

?>
<!--  show stories -->
<table>
    <thead>
    <tr>
        <th>Last Update Time</th>
        <th>Title</th>
        <th>Author</th>
        <th>Operation</th>
        <th>Viewed Count</th>
    </tr>
    </thead>
    <tbody>
    <?php
    while($stmt->fetch()) {
    ?>
    <tr>
        <td>
            <?php echo htmlentities($s_update_time);?>
        </td>
        <td>
            <a href="viewstory.php?story_id=<?php echo htmlentities($story_id);?>&visit=true"><?php echo htmlentities
                ($title);
                ?></a>
        </td>
        <td>
            <?php echo htmlentities($author);?>
        </td>
        <?php
        if(isset($_SESSION['login']) && $username == $author) {
        ?>
        <td>
        <form name="editStory" action="createstory.php" method="post">
            <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
            <input type="hidden" name="story_id" value="<?php echo htmlentities($story_id);?>"/>
            <input type="submit" name="story" value="Edit"/>
            <input type="submit" name="story" value="Delete"/>
        </form>
        </td>
        <?php
            }else {
        ?>
            <td></td>
        <?php
        }
        ?>
        <td><?php echo htmlentities($visit_count)?></td>
    </tr>
    <?php
    }
    $stmt->close();
    ?>
    </tbody>
</table>

<!--The Display Part of Paging function-->
  <?php
  printf("<div> total %s pages </div>", $pages);
  echo "<div>";
  for($i = 1;$i<$page;$i++) {
      printf("<a href='newsList.php?page=%s'>[%s]</a>", $i, $i);
  }
  printf("[%s]", $page);
  for($i=$page + 1; $i<=$pages;$i++) {
      printf("<a href='newsList.php?page=%s'>[%s]</a>", $i, $i);
  }
  echo "</div>";

  ?>


<?php
$stmt->close();
?>
<!-- user who has log in can create a new story -->
<?php if($_SESSION['login']) { ?>
<form id="createform" name="createStory" action="createstory.php" method="post" >
    <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
    <input type="submit" name="story" value="Create Story"/>
</form>
<?php } ?>

</body>

</html>