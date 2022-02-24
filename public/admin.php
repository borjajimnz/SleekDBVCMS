<?php
require '../Core.php'; 

if(!$cms->isLogged()){
    if(isset($_POST['login'])){
        $cms->login($_POST['username'],$_POST['password']);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Bootstrap Simple Login Form</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
<style>
.login-form {
    width: 340px;
    margin: 100px auto;
    font-size: 15px;
}
.login-form form {
    margin-bottom: 15px;
    background: #f7f7f7;
    box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
    padding: 30px;
}
.login-form h2 {
    margin: 0 0 15px;
}
.form-control, .btn {
    min-height: 38px;
    border-radius: 2px;
}
.btn {        
    font-size: 15px;
    font-weight: bold;
}
</style>
</head>
<body>
<div class="login-form">
    <form method="post">
        <h2 class="text-center"><?php $cms->_('Login')?></h2>
        <div class="text-center"><?php print $cms->config['app_name']?></div>
        <br>      
        <div class="form-group">
            <input type="text" name="username" class="form-control" placeholder="Username" required="required">
        </div>
        <div class="form-group">
            <input type="password" name="password" class="form-control" placeholder="Password" required="required">
        </div>
        <div class="form-group">
            <button type="submit" name="login" class="btn btn-primary btn-block"><?php $cms->_('Log in')?></button>
        </div>
        <div class="clearfix">
            <label class="float-left form-check-label"><input type="checkbox"> <?php $cms->_('Remember me')?></label>
            <a href="#" class="float-right"></a>
        </div>  
        <br>
        <a href="index.php">Go to Frontend</a>      
    </form>
</div>
</body>
</html>

<?php
    exit;
}
?>


<?php

if(isset($_GET['logout'])){
    $cms->logout();
}

if(isset($_GET['lang'])){
    $cms->setLanguage($_GET['lang']);
}

if(isset($_POST['update_row'])){
    $cms->updateInsert($_GET['p'],$_POST,$_FILES);
}

if(isset($_POST['insert_row'])){
    $cms->updateInsert($_GET['p'],$_POST,$_FILES);
}

if(isset($_POST['delete'])){
    $table = $_GET['p'];
   $cms->delete($table,$_POST['id']);
   //$cms->redirect('admin.php?p='.$_GET['p'],['success'=>'Updated']);
}

if(isset($_POST['add_translation'])){
    $cms->translationBoxAdd($_POST['insert_lang']);
}

if(isset($_GET['backup'])){
    $backup_msg = "<span class=\"text-success\">BACKUP saved ".date('Y-m-d H:i:s').".</span><br>";
    $cms->backup();
}

if(isset($_POST['update_config'])){
    if($cms->isValidJson($_POST['config_file'])){
        $cms->updateConfig($_POST['config_file']);
        $msg = "<span class=\"text-success\">JSON saved.</span><br>";
    } else {
       $msg = "<span class=\"text-danger\">Invalid JSON, no possible to save this file.</span><br>";
    }
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css" integrity="sha384-r4NyP46KrjDleawBgD5tp8Y7UzmLA05oM1iAEQ17CSuDqnUK2+k9luXQOfXJCJ4I" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/chartist.js/latest/chartist.min.css">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 90px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            z-index: 99;
        }

        @media (max-width: 767.98px) {
            .sidebar {
                top: 11.5rem;
                padding: 0;
            }
        }
            
        .navbar {
            box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .1);
        }

        @media (min-width: 767.98px) {
            .navbar {
                top: 0;
                position: sticky;
                z-index: 999;
            }
        }

        .sidebar .nav-link {
            color: #333;
        }

        .sidebar .nav-link.active {
            color: #0d6efd;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.7.2/jsoneditor.min.js" integrity="sha512-9T9AIzkTI9pg694MCTReaZ0vOimxuTKXA15Gin+AZ4eycmg85iEXGX811BAjyY+NOcDCdlA9k2u9SqVAyNqFkQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.7.2/jsoneditor.min.css" integrity="sha512-LDaPaKECzpambd6J0xPGx2s/z8EA1rAm3JzmoMgKO0VTRbXHTeE54oDLRw26eFiyBZ3Cf888tBEHzeUTYA3ddw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <nav class="navbar navbar-light bg-light p-3">
        <div class="d-flex col-12 col-md-3 col-lg-2 mb-2 mb-lg-0 flex-wrap flex-md-nowrap justify-content-between">
            <a class="navbar-brand" href="admin.php">
                <?php print $cms->config['app_name']?>
            </a>
            <button class="navbar-toggler d-md-none collapsed mb-3" type="button" data-toggle="collapse" data-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <?php if(isset($_GET['p'])){?><form method="post"> <input name="search" class="form-control form-control-dark" type="text" placeholder="<?php $cms->_('search')?> <?php $cms->_($_GET['p'])?>" value="<?php print $_POST['search'] ?? null; ?>" aria-label="<?php $cms->_('search in')?> <?php $cms->_($_GET['p'])?>"> </form><?php } ?>
        </div>
        <div class="col-12 col-md-5 col-lg-8 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
            
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-expanded="false">
                  <?php $cms->_('My Account')?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <li><a class="dropdown-item" href="index.php">Frontend</a></li>
                  <li><a class="dropdown-item" href="?logout">Sign out</a></li>
                </ul>
              </div>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                            <li class="nav-item">
                              <a class="nav-link <?php if(!isset($_GET['p'])) print 'active';?>" aria-current="page" href="admin.php">
                                <span class="ml-2"><?php $cms->_('dashboard')?></span>
                              </a>
                            </li>
                        <?php foreach($cms->config['stores'] as $storek=>$storev){ ?>
                            <li class="nav-item">
                              <a class="nav-link <?php if(isset($_GET['p']) && $_GET['p'] == $storek) print 'active';?>" aria-current="page" href="?p=<?php print $storek?>">
                                <span class="ml-2"><?php $cms->_($storek)?></span>
                              </a>
                            </li>
                        <?php } ?>


               
                    </ul>
                </div>
            </nav>
            <?php if(!isset($_GET['p'])){ ?>


    <?php
$myfile = fopen($cms->root_path.'/.default_stores', "r") or die("Unable to open config file!");
$json = fread($myfile,filesize($cms->root_path.'/.default_stores'));
fclose($myfile);
?>

                <main class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="card">
                                <h5 class="card-header">Dashboard</h5>
                                <div class="card-body">

                                </div>

                            </div>
                        </div>
                        <div class="col-sm-6">
                            <form method="post">
                            <div class="card">
                                <h5 class="card-header">Configuration</h5>
                                <div class="card-body">
                                  <textarea id="editor" class="form-control" style="min-height: 600px;" name="config_file">
<?php print $_POST['config_file'] ?? $json ?>
                                  </textarea>

                                  <div class="row p-2">
                                    <div class="col-sm-6">
                                        <?php print $msg ?? null?>
                                        <button class="btn btn-primary" name="update_config"><?php $cms->_('Update') ?></button>
                                    </div>
                                    <div class="col-sm-6 text-right">
                                        <?php print $backup_msg ?? null?>
                                        <a href="?backup"><?php $cms->_('create_backup')?></a>
                                    </div>
                                  </div>
                                </div>

                            </div>
                   
                            </form>
                        </div>
                    </div>
                </main>

            <?php } ?>
            <?php if(isset($_GET['p'])){ ?>
                <main class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="admin.php"><?php $cms->_('dashboard')?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php $cms->_($_GET['p'])?></li>
                        </ol>
                    </nav>
                    <h1 class="h2 pb-2"><?php $cms->_($_GET['p'])?></h1>
                    <div class="row">
                        <?php if(isset($_POST['insert']) || isset($_POST['update']) || isset($_POST['view'])){ ?>
                        <div class="col-12 col-xl-6">
                            
                            <div class="card mb-3">
                                <h5 class="card-header"><?php $cms->_('Create')?> <?php $cms->_($_GET['p'])?></h5>
                                <div class="card-body">
                                    <?php if(isset($_POST['insert'])) print $cms->form($_GET['p'],'insert_row'); ?>
                                    <?php if(isset($_POST['update'])) print $cms->form($_GET['p'],'update_row',$_POST['id']); ?>
                                    <?php if(isset($_POST['view'])) print $cms->form($_GET['p'],'view_row',$_POST['id']); ?>
                                </div>
                            </div>
                        </div>
                        <?php } else { ?>
                        <div class="col-12">
                            <div class="card mb-3">
                                <h5 class="card-header"><?php $cms->_('latest')?> <?php $cms->_($_GET['p'])?></h5>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <?php print $cms->table2table($_GET['p']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </main>
            <?php } ?>


        </div>

    </div>

        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/js/bootstrap.min.js" integrity="sha384-oesi62hOLfzrys4LxRF63OJCXdXDipiYWBnvTl9Y9/TRlw5xlKIEHpNyvvDShgf/" crossorigin="anonymous"></script>
<script>
    document.getElementById('editor').addEventListener('keydown', function(e) {
  if (e.key == 'Tab') {
    e.preventDefault();
    var start = this.selectionStart;
    var end = this.selectionEnd;

    // set textarea value to: text before caret + tab + text after caret
    this.value = this.value.substring(0, start) +
      "\t" + this.value.substring(end);

    // put caret at right position again
    this.selectionStart =
      this.selectionEnd = start + 1;
  }
});
</script>
</body>
</html>