<?php
    namespace Models;

    use Controllers\ControllerAdminEditPost;
    use Core\App;
    use RedBeanPHP\R;

    class Post extends Model
    {

        public function add()
        {
            if (empty($_POST)) {
                return '';
            }
            $add_post_view         = new ControllerAdminEditPost();
            $add_post_view_options = [];
            $validation            = new DataValidator();
            $_POST                 = $validation->sanitize($_POST);
            $validation->validation_rules(
                [
                    'post_title'   => 'required|max_len,60|min_len,1',
                    'post_excerpt' => 'required',
                    'post_content' => 'required',
                ]
            );
            $validated_data = $validation->run($_POST);
            $post_thumbnail = false;
            if (isset($_FILES) && ! empty($_FILES['post_thumbnail']['name'])) {
                $post_thumbnail = $_FILES['post_thumbnail'];

            }
            if ($validated_data === false) {
                $add_post_view_options['add_post_validation'][] = $validation->get_readable_errors();
                $add_post_view->action_index($add_post_view_options);

            } else {
                $post               = R::dispense('posts');
                $post->post_title   = $validated_data['post_title'];
                $post->post_excerpt = $validated_data['post_excerpt'];
                $post->post_content = $validated_data['post_content'];
                $post->post_autor   = User::getCurentUser();
                if ($post_thumbnail) {
                    $post_thumbnail_name = $post_thumbnail['name'];
                    $post_thumbnail_tmp  = $post_thumbnail['tmp_name'];
                    $uploads_dir         = App::$config->uploads_dir;
                    if (move_uploaded_file($post_thumbnail_tmp, "{$uploads_dir}{$post_thumbnail_name}")) {
                        $post_thumbnail_url   = Route::siteUrl() . '/uploads/' . $post_thumbnail_name;
                        $post->post_thumbnail = $post_thumbnail_url;
                    }
                }
                R::store($post);
                Route::pageRedirect('admin');
            }

        }

        public function edit()
        {
            if (empty($_POST)) {
                return '';
            }
            $add_post_view         = new ControllerAdminEditPost();
            $add_post_view_options = [];
            $validation            = new DataValidator();
            $_POST                 = $validation->sanitize($_POST);
            $validation->validation_rules(
                [
                    'post_ID'      => 'required|numeric',
                    'post_title'   => 'required|max_len,60|min_len,1',
                    'post_excerpt' => 'required',
                    'post_content' => 'required',
                ]
            );
            $validated_data = $validation->run($_POST);
            $post_thumbnail = false;
            if (isset($_FILES) && ! empty($_FILES['post_thumbnail']['name'])) {
                $post_thumbnail = $_FILES['post_thumbnail'];

            }
            if ($validated_data === false) {
                $add_post_view_options['add_post_validation'][] = $validation->get_readable_errors();
                $add_post_view->action_index($add_post_view_options);

            } else {
                $post               = R::load('posts', $validated_data['post_ID']);
                $post->post_title   = $validated_data['post_title'];
                $post->post_excerpt = $validated_data['post_excerpt'];
                $post->post_content = $validated_data['post_content'];
                $post->post_autor   = User::getCurentUser();
                if ($post_thumbnail) {
                    $post_thumbnail_name = $post_thumbnail['name'];
                    $post_thumbnail_tmp  = $post_thumbnail['tmp_name'];
                    $uploads_dir         = App::$config->uploads_dir;
                    if (move_uploaded_file($post_thumbnail_tmp, "{$uploads_dir}{$post_thumbnail_name}")) {
                        $post_thumbnail_url   = Route::siteUrl() . '/uploads/' . $post_thumbnail_name;
                        $post->post_thumbnail = $post_thumbnail_url;
                    }
                }
                R::store($post);
                Route::pageRedirect('admin');
            }

        }

        public function delete()
        {
            if (empty($_POST)) {
                return '';
            }
            $validation = new DataValidator();
            $_POST      = $validation->sanitize($_POST);
            $validation->validation_rules(
                [
                    'post_ID' => 'required|numeric',
                ]
            );
            $validated_data = $validation->run($_POST);
            $post = R::load('posts', $validated_data['post_ID']);
            R::trash($post);
            Route::pageRedirect('admin');
        }

        public static function getPosts()
        {
            return R::findAll('posts');
        }

        public static function getPost(int $id)
        {
            return R::findOne('posts', 'id = ?', [$id]);
        }

    }