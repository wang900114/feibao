<?php
return array(
	//应用类库不再需要使用命名空间
    'APP_USE_NAMESPACE'    =>    false,

	// 导航菜单
	'navMenu' => array(
		array(
			'title' => '工具箱',
			'menu'	=> array(
				array(
					'name'	=> 'File',
					'title'	=> '文件管理',
					'level'	=>	'2',
					'access'	=> '1',
				),
				array(
					'name'	=> 'SVN',
					'title'	=> 'SVN部署',
					'level'	=>	'2',
					'access'	=> '1',
				),
			),

		),
		array(
			'title' => '数据处理',
			'menu'	=> array(
				array(
					'name'	=> 'Jwd',
					'title'	=> '城市数据处理',
					'level'	=>	'2',
					'access'	=> '1',
				),

			),
		),
	),
);