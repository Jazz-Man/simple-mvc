<?php
    namespace Views;

    use Core\App;
    use Models\Login;

    class View
    {

        public static function generate(string $content_view, array $data = null)
        {
            $config = App::$config;
            $loader = new \Twig_Loader_Filesystem($config->template_dir);
            $loader->addPath('public/assets/css', 'css');
            $loader->addPath('public/assets/js', 'js');
            $loader->addPath('public/layout', 'layout');
            $loader->addPath('public/backend', 'backend');
            $loader->addPath('public/frontend', 'frontend');
            $twig = new \Twig_Environment($loader, $config->template_setup);
            $twig->addExtension(new \Twig_Extension_Debug());
            $navbar = ['navbar' => App::$config->pages];
            $lorem  = [
                'lorem' => [
                    'sm' => '<p>Lorem ipsum dolor sit amet. Nemo enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam. Doloribus asperiores repellat. nobis est et expedita distinctio deserunt mollitia. Aliquid ex ea voluptate velit esse quam. Vitae dicta sunt, explicabo officia deserunt mollitia animi. Eligendi optio, cumque nihil impedit. Iste natus error sit voluptatem sequi nesciunt, neque porro quisquam est. Sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa. </p>',
                    'md' => '<p>Lorem ipsum dolor sit amet. Nemo enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam. Doloribus asperiores repellat. nobis est et expedita distinctio deserunt mollitia. Aliquid ex ea voluptate velit esse quam. Vitae dicta sunt, explicabo officia deserunt mollitia animi. Eligendi optio, cumque nihil impedit. Iste natus error sit voluptatem sequi nesciunt, neque porro quisquam est. Sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa. </p><p>Quibusdam et voluptates repudiandae sint et dolorum fuga. Minus id, quod maxime placeat, facere possimus, omnis dolor sit. Asperiores repellat. laboriosam, nisi ut aliquid ex ea commodi autem. Earum rerum hic tenetur a sapiente delectus. Quaerat voluptatem accusantium doloremque laudantium, totam rem aperiam. Unde omnis voluptas assumenda est. Vitae dicta sunt, explicabo consectetur, adipisci velit, sed ut perspiciatis. Nesciunt, neque porro quisquam est, qui dolorem. </p>',
                    'lg' => '<p class="lead">Lorem ipsum dolor sit amet. Nemo enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam. Doloribus asperiores repellat. nobis est et expedita distinctio deserunt mollitia. Aliquid ex ea voluptate velit esse quam. Vitae dicta sunt, explicabo officia deserunt mollitia animi. Eligendi optio, cumque nihil impedit. Iste natus error sit voluptatem sequi nesciunt, neque porro quisquam est. Sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa. </p><p>Quibusdam et voluptates repudiandae sint et dolorum fuga. Minus id, quod maxime placeat, facere possimus, omnis dolor sit. Asperiores repellat. laboriosam, nisi ut aliquid ex ea commodi autem. Earum rerum hic tenetur a sapiente delectus. Quaerat voluptatem accusantium doloremque laudantium, totam rem aperiam. Unde omnis voluptas assumenda est. Vitae dicta sunt, explicabo consectetur, adipisci velit, sed ut perspiciatis. Nesciunt, neque porro quisquam est, qui dolorem. </p><p>Tempore, cum soluta nobis est. Eveniet, ut aut odit aut odit aut odit aut officiis debitis. Ipsum, quia non numquam eius modi tempora incidunt, ut aliquid. Molestiae non recusandae reiciendis voluptatibus maiores alias consequatur. Sapiente delectus, ut labore et aut odit aut odit. Voluptatum deleniti atque corrupti, quos dolores. Iusto odio dignissimos ducimus, qui ratione voluptatem accusantium doloremque laudantium totam. </p>'
                ]
            ];
            if (Login::checkLogin()) {
                unset($navbar['navbar']['Login']);
            } else {
                unset($navbar['navbar']['Logout'], $navbar['navbar']['Admin']);
            }
            $data = array_merge($data, $navbar, $lorem);
            echo $twig->render($content_view . '.twig', $data);
        }

    }