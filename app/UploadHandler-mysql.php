<?php
require('UploadHandler.php');

Class UploadHandlerMYSQL Extends UploadHandler
{
    protected function initialize()
    {
        //db init
        $this->dbInit();
        parent::initialize();
    }

    protected function dbInit()
    {
        $dsn = $this->options['dsn'];
        $user = $this->options['dbUserName'];
        $pass = $this->options['dbPassword'];

        try {
            $this->pdo = new PDO($dsn, $user, $pass);
        }

        catch (PDOException $e)
        {
            echo 'Connection failed: ' . $e->getMessage();
        }

        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function getUserId()
    {
        //let's use a simple method to save the user's files to different directories (based on cookies), 
        //so we only see our own uploads. This is only for the sake of the demo, you will want to use 
        //a more sophisticated method in a real environment. (User authentication, etc..)
        if (! isset($_COOKIE['UploaderUserId']))
        {
            $id = uniqid();
            setcookie('UploaderUserId', $id, time() + $this->options['userdir_time_to_live'], '/');
            $_COOKIE['UploaderUserId'] = $id; //cookies always only accessible after the next pageload, this is the workaround (because this is an ajax request)
        }  
        else {
            $id = $_COOKIE['UploaderUserId'];
        }
        return $id;

        // @session_start();
        // return session_id();
    }

    protected function fileSaveDB($name, $size, $uploadedBytes)
    {
        $userId = $this->getUserId();

        $uploadedTIme = time();

        $stmt = $this->pdo->prepare('SELECT file_name FROM files WHERE file_name = :file_name AND user_id = :user_id');
        $stmt->execute(array(
            ':file_name' => $name,
            ':user_id' => $userId
        ));

        if ($stmt->rowCount() > 0)
        {
            //its a partial upload, we need to update the record
            $uploadedTIme = time();
            $stmt = $this->pdo->prepare('UPDATE files SET files.uploaded_bytes = :uploaded_bytes, files.uploaded_time = :uploaded_time WHERE file_name = :file_name AND user_id = :user_id');
            $stmt->execute(array(
                ':uploaded_bytes' => $uploadedBytes,
                ':uploaded_time' => $uploadedTIme,
                ':file_name' => $name,
                ':user_id' => $userId
            ));
        } 

        else
        {
            $stmt = $this->pdo->prepare('INSERT INTO files (file_name, file_size, uploaded_bytes, user_id, uploaded_time) 
                                         VALUES (:name, :size, :uploaded_bytes, :user_id, :uploaded_time)');
            $stmt->execute(array(
                ':name' => $name,
                ':size' => $size,
                ':uploaded_bytes' => $uploadedBytes,
                ':user_id' => $userId,
                ':uploaded_time' => $uploadedTIme
            ));
        }
    }

    protected function getFileName($name, $type, $index, $contentRange)
    {
        return $this->trim_file_name($name, $type, $index, $contentRange);
    }

    protected function getFileObjectDB($fileName)
    {
        $userId = $this->getUserId();

        $stmt = $this->pdo->prepare('SELECT * FROM files WHERE file_name = :file_name AND user_id = :user_id');
        $stmt->execute([
            ':file_name' => $fileName,
            ':user_id'   => $userId
        ]);
        if ($stmt->rowCount() > 0 && $this->isValidFileObject($fileName)) {
            $row = $stmt->fetch();
            
            $file = new stdClass();
            $file->name = $row['file_name'];
            $file->size = $row['file_size'];
            $file->uploaded_bytes = $row['uploaded_bytes'];
            $file->url = $this->getDownloadURL($file->name);
            
            $this->setFileDeleteProperties($file);
            return $file;
        }
        return null;
    }

    protected function getFileObjectsDB($iteration_method = 'getFileObjectDB') {
        $upload_dir = $this->getUploadPath();
        if (!is_dir($upload_dir)) {
            return [];
        }
        return array_values(array_filter(array_map(
            array($this, $iteration_method),
            scandir($upload_dir)
        )));
    }

    public function get($printResponse = true) {
        if ($printResponse && isset($_GET['download'])) {
            return $this->download();
        }
        $fileName = $this->getFileNameParam();
        if ($fileName) {
            $response = array(
                substr($this->options['param_name'], 0, -1) => $this->getFileObjectDB($fileName)
            );
        } else {
            $response = array(
                $this->options['param_name'] => $this->getFileObjectsDB()
            );
        }
        return $this->generateResponse($response, $printResponse);
    }

    protected function isFileFinishedDB($file) {
        $userId = $this->getUserId();

        $stmt = $this->pdo->prepare('SELECT * FROM files WHERE file_name = :file_name AND user_id = :user_id');
        $stmt->execute(array(
            ':file_name' => $file->name,
            ':user_id' => $userId
        ));

        if ($stmt->rowCount() > 0)
        {
            $row = $stmt->fetch();
            if ($row['uploaded_bytes'] == $row['file_size'])
            {
                return true;
            }
        }
        return false;
    }

    protected function isFileExistInDB($file)
    {
        $answer = [];
        $userId = $this->getUserId();

        $stmt = $this->pdo->prepare('SELECT * FROM files WHERE file_name = :file_name AND user_id = :user_id');
        $stmt->execute(array(
                ':file_name' => $file->name,
                ':user_id' => $userId
        ));

        if ($stmt->rowCount() > 0)
        {
                $row = $stmt->fetch();
                $answer[0] = $row['uploaded_bytes'];
                $answer[1] = $row['file_size'];
        }
        return $answer;
    }

    protected function handleFileUpload($uploadedFile, $name, $size, $type, $error, $index = null, $contentRange = null)
    {
        $file = New stdClass();
        $file->name = $this->getFileName($name, $type, $index, $contentRange);
        $file->size = $this->fix_integer_overflow(intval($size));
        $file->type = $type;

        if ($this->isFileFinishedDB($file))
        {
            //if we already finished the file upload
            $this->setFileDeleteProperties($file);
            $file->url = $this->getDownloadURL($file->name);

                return $file;
        }

        $check_file_exist = $this->isFileExistInDB($file);

        if (is_array($check_file_exist) && (count($check_file_exist) > 0))
        {
            if ($contentRange[1] < $check_file_exist[0])
            {
                $file->size = $check_file_exist[0];

                    return $file;
            }
        }

        if ($this->validate($uploadedFile, $file, $error, $index))
        {
            $this->handle_form_data($file, $index);
            $upload_dir = $this->getUploadPath();

                if (! is_dir($upload_dir))
                {
                    mkdir($upload_dir, $this->options['mkdir_mode'], true);
                }

            $file_path = $this->getUploadPath($file->name);
            $appendFile = $contentRange && is_file($file_path) &&
            $file->size > $this->getFileSize($file_path);

            if ($uploadedFile && is_uploaded_file($uploadedFile))
            {
                // multipart/formdata uploads (POST method uploads)
                if ($appendFile) {
                    file_put_contents(
                        $file_path,
                        fopen($uploadedFile, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploadedFile, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $appendFile ? FILE_APPEND : 0
                );
            }
            $file_size = $this->getFileSize($file_path, $appendFile);
            if ($file_size === $file->size) {
                $this->fileSaveDB($file->name, $file->size, $file_size);
                $file->url = $this->getDownloadURL($file->name);
                if($this->options['handleImages']) {
                    list($img_width, $img_height) = @getimagesize($file_path);
                    if (is_int($img_width)) {
                        $this->handleImageFile($file_path, $file);
                    }
                }   
            } else {
                $this->fileSaveDB($file->name, $file->size, $file_size);
                $file->size = $file_size;
                if (!$contentRange && $this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file->error = 'abort';
                }
            }
            $this->setFileDeleteProperties($file);
        }
        return $file;
    }
}