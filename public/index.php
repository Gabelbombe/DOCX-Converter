<?php
    opcache_reset();
?>

<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="UTF-8">
    <title>HTML5|Uploader</title>
    <meta name="description" content="Resumable uploads with only Javascript and PHP. HTML5|Uploader.">
    <meta name="keywords" content="file upload, resumable, uploader, modern file upload, HMTL5">
    <meta property="og:description" content="Resumable upload script with MySQL backend.">
    <link rel="stylesheet" href="/css/normalize.css">
    <link rel="stylesheet" href="/css/style.css">
    <link href='//fonts.googleapis.com/css?family=Lato:300,400,900' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="/css/themes/defaultTheme.css">
</head>
<body>
<header>
    <div class="inner clearfix">


        <!-- Begin Menu -->
        <nav id="menu" class="menu">
            <ul id="tiny">
                <li class="active"><a href="//www.filson.com">Home</a></li>
                <li><a href="//github.com/ehime/DOCX-Uploader">View</a></li>
            </ul>
        </nav>
        <!-- End Menu -->
    </div>
</header>

<form id="fileupload" class="uploader" method="POST" action="handler.php" enctype="multipart/form-data">
    <div class="drop-wrapper">
        <div id="dropzone" class="dropzone">
            <h1>Drop Zone</h1>
            <p>
                Drop your files here. For security, your files will be deleted after an hour.<br>
                Max allowed file size is 20MB.
            </p>
                    <span id="browsebutton" class="fileinput-button button gray" href="">
                        Browse..
                        <input type="file" id="fileinput" name="files[]" class="fileinput" multiple />
                    </span>
        </div>
    </div>

    <!-- upload ui -->
    <div class="upload-wrapper">
        <div class="info-wrapper" id="info-wrapper">

            <div title="Remaining time" class="time-info">
                <i class="icon-clock"></i>
                <span>00:00:00</span>
            </div>

            <div title="Uploading speed" class="speed-info">
                0 KB/s
            </div>

            <button id="start-button" class="button pinkish" type="submit">Start Upload</button>
            <div style="clear:both;"></div>
        </div>

        <ul id="files" class="files">

        </ul>


        <div class="add-more fileinput-button hidden" id="add-more-button">
            <i class="icon-plus"></i> Add more files..
            <!-- second input form, it works well with the other -->
            <input type="file" name="files[]" class="" multiple />
        </div>
    </div> <!-- upload-wrapper -->
</form>

<footer>
    <div class="inner">
        <p>Created by <a href="//www.linkedin.com/ehimeprefecture" target="_blank">Jd Daniel</a></p>
    </div>
</footer>

<script id="template-upload" type="text/x-handlebars-template">
    {{#each files}}
    <li class="file-item">
        <div class="first">
                    <span class="top">
                        <span class="filename">{{shortenName name}}</span>
                        <a href="#" class="download-link" target="_blank">
                            <i class="icon-download"></i>
                        </a>
                    </span>
            <span class="cancel-single"><i class="icon-cancel"></i></span>
            <span class="pause"><i class="icon-pause"></i></span>
            {{#if error}}
            <div class="error">
                <i class="icon-block icon"></i>
                <span>Error: </span>
                <span>{{error}}</span>
            </div>
            {{/if}}
        </div>
        <div class="second">
            <span class="filesize">{{formatFileSize size}}</span>
            <div class="progress-wrap">
                <div class="progress">
                    <div class="bar" style="width:0%"></div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="aside">
            <div class="success-tick">
                <i class="icon-ok"></i>
            </div>
        </div>
    </li>
    {{/each}}
</script>




<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="/js/vendor/jquery-1.9.1.min.js"><\/script>')</script>

<!-- handlebars -->
<script src="/js/handlebars.min.js"></script>

<!-- uploader -->
<script src="/js/jquery.ui.widget.js"></script>
<script src="/js/vendor/bootstrap-transition.js"></script>
<script src="/js/jquery.iframe-transport.js"></script>
<script src="/js/jquery.fileupload.js"></script>
<script src="/js/jquery.fileupload-process.js"></script>
<script src="/js/jquery.fileupload-validate.js"></script>
<script src="/js/uploader.js"></script>
<script src="/js/main.js"></script>

</body>
</html>