<?php
    namespace Controllers;

    use Models\Post;

    class ControllerAdminEditPost extends Controller
    {
        public function action_index(array $options = [])
        {
            $post    = [
                'post_data' => $_POST,
                'action'    => 'add_post'
            ];
            $options = array_merge($post, $options);
            $this->view_backend->render('edit-post', $options);
        }

        public function editPost($post_id)
        {
            $post = [
                'post_data' => Post::getPost($post_id),
                'action'    => 'edit_post'
            ];
            $this->view_backend->render('edit-post', $post);
        }

        public function deletePost($post_id)
        {
            $post = [
                'post_data' => Post::getPost($post_id),
                'action'    => 'delete_post'
            ];
            $this->view_backend->render('delete-post', $post);
        }
    }