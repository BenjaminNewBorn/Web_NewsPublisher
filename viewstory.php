<?php
session_start();


$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

if($_SESSION['login'] && isset($_POST['token']) && !empty($_POST['token'])) {
    if(!hash_equals($_SESSION['token'], $_POST['token'])){
        die("Request forgery detected");
    }
}

require "database.php";
$story_id =$_GET['story_id'];

/*
 * Creation Part: Visit count
 * if user view a story, the visit count of this story + 1
*/
if(isset($_GET['visit'])) {
    $stmt = $mysqli->prepare("UPDATE stories SET visit_count = visit_count + 1 WHERE id = ?");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt->bind_param('i', $story_id);
    $stmt->execute();
    $stmt->close();
}

/*
 * Creation Part: Realize Paging function
*/
//set the number of columns each page
$page_size = 5;
//get the total number of stories
$stmt = $mysqli->prepare("SELECT count(*) FROM comments where s_id=?");
if(!$stmt) {
    printf("Query1 Prep Failed: %s\n", $mysqli->error);
    exit;
}
$stmt->bind_param('i', $story_id);;
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



//add comment
if(isset($_POST['comment'])) {
    if($_POST['comment'] == "Add Comment") {
        $stmt = $mysqli->prepare("INSERT INTO comments (content, s_id, user_id, update_time) VALUES (?,?,?,now())");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('sii', $_POST['commentContent'], $story_id, $user_id);
        $stmt->execute();
        if($stmt->affected_rows != 1) {
            printf("Add Comment Failed: %s\n", $mysqli->error);
            $stmt->close();
        }else {
            $stmt->close();
            header("Location:viewstory.php?story_id=$story_id");
            exit();
        }
    }//update comment
    elseif($_POST['comment'] == "Update") {
        $commentContent = $_POST['commentContent'];
        $stmt = $mysqli->prepare("UPDATE comments SET content = ?, update_time = now() WHERE id = ?");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('si', $commentContent, $_POST['comment_id']);
        $stmt->execute();
        $stmt->close();
        header("Location:viewstory.php?story_id=$story_id");
        exit();
    }//delete comment
    elseif($_POST['comment'] == "Delete") {
        $stmt=$mysqli->prepare("DELETE FROM comments WHERE id = ?");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('i', $_POST['comment_id']);
        $stmt->execute();
        if($stmt->affected_rows != 1) {
            printf("Delete Comment Failed: %s\n", $mysqli->error);
            $stmt->close();
        } else {
            $stmt->close();
            header("Location:viewstory.php?story_id=$story_id");
            exit();
        }
    }
}


/*
 * Creation Part: User can display if they like this story
 */
if(isset($_POST['likes']) && !empty($_POST['likes'])) {
    if("Like" == $_POST['likes']) {
        $stmt = $mysqli->prepare("INSERT INTO likes (u_id, s_id) VALUES (?,?)");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('ii', $user_id, $story_id);
        $stmt->execute();
        if($stmt->affected_rows != 1) {
            printf("Insert likes Failed: %s\n", $mysqli->error);
            $stmt->close();
        } else {
            $stmt->close();
            header("Location:viewstory.php?story_id=$story_id");
            exit();
        }
    }elseif("Not like" == $_POST['likes']) {
        $stmt = $mysqli->prepare("DELETE FROM likes WHERE u_id =? AND s_id =?");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('ii', $user_id, $story_id);
        $stmt->execute();
        if($stmt->affected_rows != 1) {
            printf("Delete likes Failed: %s\n", $mysqli->error);
            $stmt->close();
        } else {
            $stmt->close();
            header("Location:viewstory.php?story_id=$story_id");
            exit();
        }
    }
}
//get how many people like this story
$stmt = $mysqli->prepare("SELECT COUNT(u_id) FROM likes WHERE s_id = ?");
if(!$stmt){
    printf("Query Prep Failed: %s\n", $mysqli->error);
    exit;
}
$stmt->bind_param('i', $story_id);
$stmt->execute();
$stmt->bind_result($like_num);
$stmt->fetch();
$stmt->close();

//Get whether current user likes this article
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM likes WHERE s_id = ? AND u_id = ?");
if(!$stmt){
    printf("Query Prep Failed: %s\n", $mysqli->error);
    exit;
}
$stmt->bind_param('ii', $story_id, $user_id);
$stmt->execute();
$stmt->bind_result($likeOrNot);
$stmt->fetch();
$stmt->close();

?>


<!DOCTYPE html>
<html>
<head>
    <title>View Story</title>
    <link rel="stylesheet" type="text/css" href="newsWeb.css">
</head>
<body>

<!--display Story-->
<a href="newsList.php">return</a>
<?php
//query story from database, And get
$stmt = $mysqli->prepare("SELECT count(*), s.title, s.content, s.link, u.name, s.update_time, s.visit_count FROM stories s, users
 u WHERE s.author = u.id AND s.id = ?" );
if(!$stmt) {
    printf("Query Prep Failed: %s\n", $mysqli->error);
    exit;
}
$stmt->bind_param('i', $story_id);
$stmt->execute();
$result = $stmt->bind_result($cnt, $title, $content, $link, $author,$s_update_time, $visit_count);
$stmt->fetch();
?>
<table>
    <?php
    //show story
    if($cnt != 1) {
        printf("Query Failed: %s\n", $mysqli->error);
    }else {
    ?>
        <caption><?php echo htmlentities($title);?></caption>
        <tr>
            <th colspan="4"><?php printf("Author: %s", htmlentities($author));?></th>
            <th><?php echo htmlentities($s_update_time);?></th>
        </tr>
        <tr>
            <td colspan="5"><?php echo htmlentities($content);?></td>
        </tr>
        <tr>
            <td>
                <form name="likeForm" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>?story_id=<?php echo htmlentities($story_id) ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
                    <?php if(($likeOrNot != 1) && $_SESSION['login']) {?>
                        <input class="like" type="submit" name="likes" value="Like"/>
                    <?php
                    }else {
                    ?>
                        <input class="like" type="submit" name="likes" value="Not like"/>
                    <?php
                    }
                    ?>
                </form>
            </td>
            <td class="like"><?php echo htmlentities($like_num); ?></td>
            <td colspan="2"><a href="<?php echo htmlentities($link);?>"> <?php echo htmlentities($link);?></a></td>
            <td><?php printf("%s Viewed", $visit_count)?></td>
        </tr>
        <tr>
            <td colspan="5"></td>
        </tr>

        <tr>
            <th colspan="5">Comments</th>
        </tr>
        <tr>
            <th colspan="2">Name</th>
            <th style="width: 60%">Content</th>
            <th style="width: 15%">Operation</th>
            <th style="width: 15%">Last Update Time</th>
        </tr>

    <?php
    }
    $stmt->close();

    //Query Comments From database
    $stmt = $mysqli->prepare("SELECT c.id, c.content, u.name, c.update_time FROM comments c, users u WHERE u.id = c.user_id AND c.s_id = ? ORDER BY
 c.update_time DESC LIMIT ?,?");
    if(!$stmt) {
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt->bind_param('iii', $story_id, $offset, $page_size);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        //Show comments
    ?>
        <tr>
            <td colspan="2"><?php echo htmlentities($row['name']);?></td>
            <td><?php echo htmlentities($row['content'])?></td>
            <?php if(isset($_SESSION['login']) && $username == $row['name']) {;?>
                <td>
                    <form name="editComment" action="<?php echo htmlentities($_SERVER['PHP_SELF']);?>?story_id=<?php echo htmlentities($story_id) ?>" method="post">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
                        <input type="submit" name="comment" value="Edit">
                        <input type="hidden" name="comment_id" value="<?php echo htmlentities($row['id']);?>">
                        <input type="submit" name="comment" value="Delete">
                    </form>
                </td>
            <?php } else {?><td></td><?php }?>
            <td><?php echo htmlentities($row['update_time'])?></td>
        </tr>
    <?php
    }
    $stmt->close();
    ?>

</table>
<!--The Display Part of Paging function-->
<?php
printf("<div> total %s pages </div>", $pages);
echo "<div>";
for($i = 1;$i<$page;$i++) {
    printf("<a href='viewstory.php?story_id=%s&page=%s'>[%s]</a>",$story_id, $i, $i);
}
printf("[%s]", $page);
for($i=$page + 1; $i<=$pages;$i++) {
    printf("<a href='viewstory.php?story_id=%s&page=%s'>[%s]</a>",$story_id, $i, $i);
}
echo "</div>";


/*
 * When user try to edit one comment, display update comment form and show the old content of this comment, and hide
the add column form
*/
if(isset($_SESSION['login'])) {
    if ($_POST['comment'] == "Edit") {
        $stmt=$mysqli->prepare("SElECT count(*), content FROM comments WHERE id = ?");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('i', $_POST['comment_id']);
        $stmt->execute();
        $stmt->bind_result($cnt, $commentContent);
        $stmt->fetch();
        if($cnt != 1) {
            printf("Cannot find the comment");
            $stmt->close();
        }
        ?>
        <form name="change comment" action="<?php echo htmlentities($_SERVER['PHP_SELF']);?>?story_id=<?php echo htmlentities
        ($story_id) ?>" method="post">
            <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
            <textarea name="commentContent" placeholder="comment" cols="50" rows="10" required><?php echo htmlentities($commentContent);?></textarea>
            <input type="hidden" name="comment_id" value="<?php echo htmlentities($_POST['comment_id'])?>"/>
            <input type="submit" name="comment"  value="Update"/>
            <!--
            <input type="submit" name="comment" value="Cancel"/>
            -->
        </form>

        <?php
        $stmt->close();
    } else {
        ?>
        <!-- add comment form-->
        <form name="add comment" action="<?php echo htmlentities($_SERVER['PHP_SELF']);?>?story_id=<?php echo htmlentities($story_id) ?>" method="post">
            <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
            <textarea name="commentContent" placeholder="comment" cols="50" rows="10" required></textarea><br>
            <input type="submit" name="comment"  value="Add Comment">
        </form>
        <?php
    }
}
?>

</body>

</html>
