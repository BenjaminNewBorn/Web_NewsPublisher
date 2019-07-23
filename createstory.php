<?php
session_start();
if(!$_SESSION['login']){
    header("Location: newsList.php");
}else {
    if(isset($_POST['token']) && !empty($_POST['token'])) {
        if(!hash_equals($_SESSION['token'], $_POST['token'])){
            die("Request forgery detected");
        }
    }
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

require "database.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Title</title>
    <link rel="stylesheet" type="text/css" href="newsWeb.css">
</head>
<body>
<h3>Welcome, <?php echo htmlentities($username)?></h3><br>
<a href="newsList.php">Return</a>
    <?php
    if(isset($_POST['story'])) {
        if("Edit" == $_POST['story']) {
            $story_id=$_POST['story_id'];
            $stmt = $mysqli->prepare("SELECT count(*),title, content, link FROM stories WHERE id=? ");
            if(!$stmt) {
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('i', $story_id);
            $stmt->execute();
            $stmt->bind_result($cnt, $title, $content,$link);
            $stmt->fetch();
            if($cnt == 1) {
    ?>
                <form id="updateStory" name="updateStory" action="<?php echo htmlentities($_SERVER['PHP_SELF']);?>"
                      method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
                    <input type="text" placeholder="Title" value="<?php echo htmlentities($title);?>" name="title"
                           size="100%"
                           id="title"
                           required/><br><br>
                    <textarea name="content"  placeholder="content" cols="100" rows="40" required><?php echo htmlentities
                    ($content); ?></textarea><br><br>
                    <input type="text" placeholder="Link" name="link" size="80%"  value="<?php echo htmlentities($link);?>" /><br><br>
                    <input type="hidden" name="story_id" value="<?php echo htmlentities($story_id);?>"/>
                    <input type="submit" name="submit" value="Update"/>
                </form>
    <?php
            }else {
                echo "Cannot get story, please retry!";
            }
        }elseif("Delete" == $_POST['story']) //Delete story
        {
            $story_id = $_POST['story_id'];
            //Delete comments of this story
            $stmt = $mysqli->prepare("DELETE FROM comments WHERE s_id=?");
            if(!$stmt) {
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('i', $story_id);
            $stmt->execute();

            //Delete story
            $stmt = $mysqli->prepare("DELETE FROM stories WHERE id=?");
            if(!$stmt) {
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('i', $story_id);
            $stmt->execute();
            if($stmt->affected_rows != 1) {
                printf("Delete story Failed: %s\n", $mysqli->error);
                $stmt->close();
                exit;
            } else {
                $stmt->close();
                header("Location:newsList.php");
                exit();
            }
        }else{
    ?>
            <form id="storyform" name="storyinput" action="<?php echo htmlentities($_SERVER['PHP_SELF'])?>" method="post">
                <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
                <input type="text" placeholder="Title" name="title" size="80%" id="title" required/><br><br>
                <textarea name="content" placeholder="content" cols="100" rows="20" required></textarea><br><br>
                <label for="link" class="link">Link:</label>
                <input type="text" name="link" size="80%" id="link" /><br><br>
                <input type="submit" name="submit" value="Submit" />
            </form>
    <?php
        }
    }
    ?>


<?php
if(isset($_POST['submit'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $link = $_POST['link'];
}
if($_POST['submit'] == "Submit") //Submit new story
{
    $stmt = $mysqli->prepare("INSERT INTO stories (title, content, link, author,update_time) VALUES (?,?,?,?,now())");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt->bind_param('sssi', $title, $content, $link, $user_id);
    $stmt->execute();
    if($stmt->affected_rows == 1) {
        $stmt->close();
        header("Location:newsList.php");
        exit();
    } else{
        printf("Create Story Failed: %s\n", $mysqli->error);
    }
    $stmt->close();
} elseif($_POST['submit'] == "Update")  //Update change story
{
    $story_id = $_POST['story_id'];
    $stmt = $mysqli->prepare("UPDATE stories SET title = ?, content = ?, link = ?, update_time = now() WHERE id= ?");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt->bind_param('sssi', $title,$content,$link, $story_id);
    $stmt->execute();
    $stmt->close();
    header("Location:newsList.php");
    exit();
}


?>

</body>
</html>