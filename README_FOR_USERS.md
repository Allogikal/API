<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Руководство по разработке проекта

В первую очередь для корректной работы api необходимо убедиться в том установлен верный префикс для запросов
Лезем в нужный провайдер для изменения этого префикса (в коллекции и запросах стоит 'api' но его можно будет изменить)
Путь `app/Providers/RouteServiceProvider.php`

```php
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
        // Здесь изменяется префикс, можно поставить например 'api-v2'
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Здесь можно конфигурировать количество запросов
     * отправляемых клиентом в минуту 
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
```

Теперь когда мы уверены в работе запросов, приступаем к контроллерам.
Подготовления проводятся следующим образом. И начинать необходимо с миграций.
Ларка автоматически создает миграцию и модель пользователя, дополним ее

##### Миграция users
```php
public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('login')->unique();
            $table->string('password');
            $table->string('photo_file')->nullable();
            $table->string('status')->default('not working');
            // Здесь создается внешний ключ по которому можно связаться с таблицей roles
            $table->foreignId('role_id');
            $table->rememberToken();
            $table->timestamps();
        });
    }
```
##### Модель User

```php
    protected $fillable = [
        'login',
        'password',
        'role_id',
        'photo_file',
        'name',
        'status'
    ];

    // Сразу создаем методы для связанной таблицы, чтобы вывести роль
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // Сразу создаем методы для связанной таблицы, чтобы вывести смены пользователя
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class);
    }
```
Делаем миграции поочередно, сделаем миграцию, модель и фабрику (-mf создаем по модели и фабрику и миграцию): 
- Позиций меню (товаров).

```php
php artisan make:model Position -mf
```

##### Миграция positions
```php
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->integer('count')->default(1);
            $table->string('position');
            $table->integer('price');
            $table->timestamps();
        });
    }
```
##### Модель Position

```php
    protected $fillable = [
      'count',
      'position',
      'price',
    ];
```

- Роли пользователя.

```php
php artisan make:model Role -mf
```

##### Миграция roles
```php
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_title');
            $table->timestamps();
        });
    }
```
##### Модель Role

```php
    protected $fillable = [
        'role_title'
    ];
```

- Заказа.

```php
php artisan make:model Order -mf
```

##### Миграция orders
```php
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('table');
            $table->integer('number_of_person')->nullable();
            $table->timestamps();
            $table->string('status');
            $table->integer('price_all')->default(0);
            // Здесь та же связь по внешнему ключу в бд
            // Но бывает такое что ларка автоматически не находит целевую таблицу
            // С помощью constrained мы показываем какая таблица ему нужна
            $table->foreignId('position_id')->nullable()->constrained('positions');
        });
    }
```
##### Модель Order

```php
    protected $fillable = [
        'order_id',
        'table',
        'number_of_person',
        'status',
        'positions',
        'price_all'
    ];
    // Здесь показываем смены в которых заказ присутствует
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class);
    }
    // Здесь показываем работников которые работают с заказом
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    // Здесь показываем позиции в заказе
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class);
    }
```

- Смен.

```php
php artisan make:model Shift -mf
```

##### Миграция shifts
```php
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->dateTime('start');
            $table->dateTime('end');
            // Здесь та же связь по внешнему ключу в бд
            // Но бывает такое что ларка автоматически не находит целевую таблицу
            // С помощью constrained мы показываем какая таблица ему нужна
            // Ну и пусть по дефолту будет null, а на удаление поставим каскад
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            // Таким образом можно задавать значение по default если в request такое поле не приходит - оочень удобно
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }
```
##### Модель Shift

```php
    protected $fillable = [
      'start', 'end', 'active', 'shift_id'
    ];
    // Здесь получаем пользователей смены
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    // Здесь получаем все заказы по смене
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }
```

А также для:

- Связи заказа и позиций меню.

```php
php artisan make:model OrderPosition -mf
```

##### Миграция order_positions
```php
    public function up(): void
    {
        Schema::create('order_position', function (Blueprint $table) {
            $table->id();
            // Делаем связь многие ко многим, и сюда же счетчик для количества позиций
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('position_id')->constrained('positions')->onDelete('cascade');
            $table->integer('count')->default(1);
            $table->timestamps();
        });
    }
```
##### Модель OrderPosition

```php
    protected $fillable = [
        'order_id', 'position_id', 'count'
    ];
    protected $table = 'order_position';
```

- Связи заказа и пользователей (работников).

```php
php artisan make:model OrderUser -mf
```

##### Миграция order_users
```php
    public function up(): void
    {
        Schema::create('order_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }
```
##### Модель OrderUser

```php
    protected $fillable = [
        'order_id', 'user_id'
    ];
    protected $table = 'order_user';
```

- Связи пользователя и смены.

```php
php artisan make:model UserShift -mf
```

##### Миграция shift_users
```php
    public function up(): void
    {
        Schema::create('shift_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
```
##### Модель UserShift

```php
    protected $fillable = [
      'user_id', 'shift_id'
    ];
    protected $table = 'shift_user';
```
А как же заполнить таблицу определенными данными?
Можно было бы использовать фабрики (factory) для создания рандомно сгенерированных символов.
Но нужен определенный осмысленный текст, так что пропишем все в одном DatabaseSeeder

```php
public function run(): void
    {
        /**
         * Добавляем роли
         */
         Role::factory()->create([
             'role_title' => 'Администратор'
         ]);
        Role::factory()->create([
            'role_title' => 'Официант'
        ]);
        Role::factory()->create([
            'role_title' => 'Повар'
        ]);

        /**
         * Создаем пользователей
         */
        User::factory()->create([
            'login' => 'admin',
            'password' => Hash::make('admin'),
            'role_id' => 1,
            'photo_file' => null,
            'name' => 'Alex',
            'status' => 'not working'
        ]);

        User::factory()->create([
            'login' => 'waiter',
            'password' => Hash::make('waiter'),
            'role_id' => 2,
            'photo_file' => null,
            'name' => 'Bell',
            'status' => 'not working'
        ]);

        User::factory()->create([
            'login' => 'cook',
            'password' => Hash::make('cook'),
            'role_id' => 3,
            'photo_file' => null,
            'name' => 'Estrella',
            'status' => 'not working'
        ]);
        /**
         * Создаем позиции меню которые можно будет добавлять
         */
        Position::factory()->create([
            'position' => 'Position 1',
            'price' => '2000'
        ]);

        Position::factory()->create([
            'position' => 'Position 2',
            'price' => '8192'
        ]);

        Position::factory()->create([
            'position' => 'Position 3',
            'price' => '3200'
        ]);

        Position::factory()->create([
            'position' => 'Position 4',
            'price' => '1220'
        ]);
```
С базой данных закончили, переходим к рабочей логике.
Первым делом необходимо сделать ограничение пользователей по привелегиям
Стандартный набор привелегий это функционал для пользователя, повара и официанта
Для доступа к функционалу определенной роли можно прописать middlewares

Для роли администратора
```php
php artisan make:middleware IsAdministrator
```
Здесь прописываем
```php
    public function handle(Request $request, Closure $next): Response
    {
    // если не соответствует роли администратора, запрос не проходит
        if(Auth::user()->role_id != 1) {
            return response()->json([
                "error" => [
                    "code" => 403,
                    "message" => "Вы не администратор"
                ]
            ], 403, [ "Content-type" => "application/json" ]);
        }

        return $next($request);
    }
```

Для роли официанта
```php
php artisan make:middleware IsWaiter
```
Здесь прописываем
```php
    public function handle(Request $request, Closure $next): Response
    {
    // если не соответствует роли официанта, запрос не проходит
        if(Auth::user()->role_id != 2){
            return response()->json([
                "error" => [
                    "code" => 403,
                    "message" => "Вы не официант"
                ]
            ], 403, [ "Content-type" => "application/json" ]);
        }

        return $next($request);
    }
```

Для роли повара
```php
php artisan make:middleware IsCook
```
Здесь прописываем
```php
    public function handle(Request $request, Closure $next): Response
    {
    // если не соответствует роли повара, запрос не проходит
        if(Auth::user()->role_id != 3){
            return response()->json([
                "error" => [
                    "code" => 403,
                    "message" => "Вы не повар"
                ]
            ], 403, [ "Content-type" => "application/json" ]);
        }

        return $next($request);
    }
```

Также немного изменим существующий Authenticate.php middleware для вывода сообщения
в случае если не авторизованный пользователь лезет в закрытый для него функционал.
```php
    protected function redirectTo(Request $request): ?string
    {
        // будет перенаправлять на нужный нам метод контроллера авторизации "неавторизован"
        return $request->expectsJson() ? null : route('unauthorization');
    }
```

И авторизуем их в `app/Http/Kernel.php`
```php
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'authorize' => AuthMiddleware::class,
        // Созданные middlewares
        'admin' => isAdministrator::class,
        'waiter' => isWaiter::class,
        'cook' => isCook::class,
    ];
```

В дальнейшем используем написанные middlewares
Перед тем как писать контроллеры и сервисы, выполняющий свой спектр задач,
необходимо валидировать поля в запросе пользователя, в случае несоответствия выводить нужные ошибки валидации.
С этим прекрасно справляются requests
Создаем несколько таких сущностей:

- Работает на валидацию в авторизации
```php
php artisan make:request Auth/LoginRequest
```
```php
public function rules(): array
    {
        return [
        // Поля обязательны, принимает только строку
            'login' => 'required|string',
            'password' => 'required|string'
        ];
    }
```

- Работает на валидацию в заказах
```php
php artisan make:request Order/OrderRequest
```
```php
    public function rules(): array
    {
        return [
        // Поля обязательны, принимает только целые числа
            'work_shift_id' => 'required|integer',
            'table_id' => 'required|integer',
            'number_of_person' => 'required|integer'
        ];
    }
```

```php
php artisan make:request Order/StatusRequest
```
```php
    public function rules(): array
    {
        return [
            'status' => 'required|string'
        ];
    }
```

- Работает на валидацию в позициях меню
```php
php artisan make:request Position/PositionRequest
```
```php
    public function rules(): array
    {
        return [
            'menu_id' => 'required|integer',
            'count' => 'required|integer',
        ];
    }
```

- Работает на валидацию в пользователях
```php
php artisan make:request User/UserRequest
```
```php
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'surname' => 'string',
            'patronymic' => 'string',
            // unique ссылается на таблицу пользователей и
            // в случае если в таблице есть пользователь с таким логином
            // он не создаст такого пользователя
            'login' => 'required|string|unique:users',
            'password' => 'required|string',
            // принимает только файловые типы
            'photo_file' => 'file',
            'role_id' => 'required|integer'
        ];
    }
```

- Работает на валидацию в работниках и сменах
```php
php artisan make:request WorkShift/addUserRequest
```
```php
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer'
        ];
    }
```

```php
php artisan make:request WorkShift/ShiftRequest
```
```php
    public function rules(): array
    {
        return [
        // принимает значение по заданному паттерну для даты
            'start' => 'required|date_format:Y-m-d H:i',
            'end' => 'required|date_format:Y-m-d H:i',
        ];
    }
```

### Важное примечание!!! Во всех requests нужно сделать такие сущности авторизованными
```php
    public function authorize(): bool
    {
    // везде изменить с false на true
        return true;
    }
```

Все правила валидации есть на страницах оф. документации, 
и стоит ими пользоваться, так как это дает возможность сделать хорошую валидацию запросов
на стороне сервера.
Вот ссылочка - https://laravel.com/docs/10.x/validation#available-validation-rules

Теперь можно приступить к работе логики определенных сущностей
Обычно бизнес-логикой серверной части приложения или того же сайта занимаются контроллеры
Но для более централизованной работы такой логики мы выносим основные операции в сервисы
А определенные сервисы уже будут работать на контроллеры, которые будут заниматься
различными проверками, возможно дополнительной обработкой данных.
Структура проекта не на 100% совершенна, так что возможно размытие рамок работы
сервисов и контроллеров.
Можно начать с работы сервисов. Создадим такие сервисы, их всего три основных.
Они создаются в ручную, можно прописать их в директориях

Services/OrderService.php в каталоге app
Services/ShiftService.php в каталоге app
Services/UserService.php в каталоге app

- Сервис заказов
```php
    // выводим все заказы
    public function orders(): Collection
    {
        return Order::all();
    }
    // выводим все заказы со статусом принят
    // нужен для определенного специфичного запроса
    public function index(): Collection
    {
        return Order::all()->where('status', 'Принят');
    }
    // выводим только один заказ по id пришедший из запроса
    public function show($id)
    {
        return Order::find($id);
    }
    // Создаем заказ и также связь в связанной таблице
    public function add_order($request)
    {
        $order = Order::create([
            'table' => $request['table_id'],
            'number_of_person' => $request['number_of_person'],
            'status' => 'Принят',
            'price_all' => 0,
        ]);
        OrderUser::create([
            'order_id' => $order->id,
            'user_id' => Auth::user()->id
        ]);
        
        return $order;
    }
    // добавляем позицию в заказ
    public function add_position($id, $request) {
        return OrderPosition::create([
            'order_id' => $id,
            'position_id' => $request['menu_id']
        ]);
    }
    // удаляем позицию из заказа, но делаем это в связанной таблице!
    public function delete_position($id, $position_id): void
    {
        OrderPosition::where('position_id', $position_id)->delete();
    }
    // изменяем статус заказа
    public function change_status($id, $request)
    {
        return Order::find($id)->update([
            'status' => $request['status']
        ]);
    }
```

- Сервис смен
```php
    // выводим все смены
    public function index(): Collection
    {
        return Shift::all();
    }
    // добавляем новую смену
    public function store($request)
    {
        return Shift::create([
            'start' => $request['start'],
            'end' => $request['end'],
        ]);
    }
    // открываем смену, с проверками на наличие смены и не открыта ли эта смена
    public function open($id)
    {
        $work_shift = Shift::find($id);
        if(!$work_shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены нет'
                ]
            ], 404);
        }
        if($work_shift->active) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Эта смена уже открыта!'
                ]
            ], 404);
        }
        $work_shift->update([
            'active' => true
        ]);

        return $work_shift;
    }
    // закрываем смену, с проверками на наличие смены и не закрыта ли эта смена
    public function close($id)
    {
        $work_shift = Shift::find($id);
        if(!$work_shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены нет'
                ]
            ], 404);
        }
        if(!$work_shift->active) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Эта смена уже закрыта!'
                ]
            ], 404);
        }
        $work_shift->update([
            'active' => false
        ]);

        return $work_shift;
    }
    // добавляем сотрудника на смену
    public function add($id, $request)
    {
        return UserShift::create([
            'user_id' => $request['user_id'],
            'shift_id' => $id
        ]);
    }
    // удаляем сотрудника со смены
    public function delete($id, $user_id): void
    {
        UserShift::where('shift_id', $id)->where('user_id', $user_id)->delete();
    }
```

- Сервис пользователей
```php
    // вывод всех пользователей
    public function index(): Collection
    {
        return User::all();
    }
    // добавление пользователей
    public function store(UserRequest $request)
    {
        // сам файл с картинкой
        $photo_file = $request['photo_file'];
        // путь картинки
        $path = null;
        // Здесь файл картинки загружается в storage
        // и парралельно создается ссылка полного пути к картинке
        // которая находиться в хранилище
        // этот путь и заносится в БД
        if($photo_file){
            $path = url(Storage::disk('public')->put('photos', $photo_file));
        }
        return User::create([
            'name' => $request['name'],
            'surname' => $request['surname'],
            'email' => $request['email'],
            'patronymic' => $request['patronymic'],
            'login' => $request['login'],
            'password' => $request['password'],
            'photo_file' => $path,
            'role_id' => $request['role_id'],
        ]);
    }
    // вывод одного пользователя
    public function show($id)
    {
        $user = User::find($id);
        if(!$user){
            return false;
        }

        return $user;
    }
    // удаление пользователя, если он существует
    public function destroy($id)
    {
        $user = User::find($id);
        if(!$user){
            return false;
        }
        // Здесь чистим с хранилища фото пользователя
        if($user->photo_file){
            Storage::disk('public')->delete($user->photo_file);
        }

        return $user->delete();
    }
```

Сервисы прописаны, но не хватает вывода данных в виде массива, для этого
можно использовать ресурсы (resources). Они дают возможно создавать коллекции
из уже обработанных данных. Создаем.

- Ресурс заказов

```php
php artisan make:resource OrderResource
```

```php
    public function toArray(Request $request): array
    {
        return [
            // Обращаемся к сущности(модели) и выводим поле
            'id' => $this->id,
            'table' => $this->table,
            'status' => $this->status,
            // таким образом можно обращаться и к функциям,
            // для вывода связанных таблиц в БД
            'workers' => $this->users,
            'positions' => $this->positions,
            'price_all' => $this->price_all,
        ];
    }
```

- Ресурс смен

```php
php artisan make:resource ShiftResource
```

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start' => $this->start,
            'end' => $this->end,
            'active' => $this->active,
            'workers' => $this->users,
        ];
    }
```

- Ресурс пользователей

```php
php artisan make:resource UserResource
```

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'login' => $this->login,
            'status' => $this->status,
            'group' => $this->role->role_title,
            'shifts' => $this->shifts
        ];
    }
```

И в конечном итоге все готово для разработки контроллеров, используя созданные
сервисы, реквесты, и ресурсы с моделями

Пишем контроллеры и сервис-контроллеры для доступа к нашим сервисам:

- Контроллер авторизации

```php
php artisan make:controller Auth/AuthController
```
```php
    // метод авторизации
    public function logIn(LoginRequest $request):JsonResponse {
    // происходит проверку на соответствие полей,
    // логина и зашифрованного пароля (attempt шифрует пароли сам перед их проверкой в бд)
        if (!Auth::attempt($request->all())) {
            return response()->json([
                "error" => [
                    "code" => 401,
                    "message" => "Неудачная авторизация!"
                ]
            ], 401, [ "Content-type" => "application/json" ]);
        }
        // записываем авторизованного пользователя и создаем ему токен
        // токены хранятся в отдельной таблице в БД
        // так как авторизация выполнена популярным методом Bearer токенизацией
        $user = Auth::user();
        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            "data" => [
                "user_token" => $token
            ]
        ], 200, [ "Content-type" => "application/json" ]);
    }

    public function logOut():JsonResponse {
    // Здесь мы очищаем токены авторизованного пользователя
    // происходит его выход из системы
    // токены "сгорают"
        auth()->user()->tokens()->delete();

        return response()->json([
            "data" => [
                "message" => "Успешный выход!"
            ]
        ], 200, [ "Content-type" => "application/json" ]);
    }

    // возвращает сообщение о том что пользователь не авторизован
    // как раз таки тот самый метод, используемый в middleware Authenticate.php
    public function unauthorization():JsonResponse {
        return response()->json([
            'error' => [
                'code' => 403,
                'message' => 'Вы не авторизованы!'
            ]
        ], 403, [ "Content-type" => "application/json" ]);
    }
```

- Контроллер заказов

```php
php artisan make:controller Order/ServiceController
```
```php
    // Создает поле для обращения к классу
    public OrderService $service;
    // привязывает к полю созданный экземпляр класса OrderService
    // после этого в контроллерах можно использовать методы привязанного сервиса
    public function __construct(OrderService $service){
        $this->service = $service;
    }
```

```php
php artisan make:controller Order/OrderController
```
```php
    /**
     * Display orders in shift.
     */
    public function orders($id): JsonResponse
    {
    // Выводим все заказы через сервис и его доступные нам методы
        $orders = $this->service->orders();
        // Находим смену если она есть
        $shift = Shift::query()->find($id);
        if(!$shift) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой смены не существует!'
            ], 404);
        }
        // Выводим все заказы через коллекцию (созданные ресурсы)
        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Display orders in shift.
     */
    public function taken(): JsonResponse
    {
        $orders = $this->service->orders();
        // Вывод тех заказов которые видны только работникам находящимся в смене
        $id = UserShift::select('shift_id')->where('user_id', Auth::id())->first();
        $shift = Shift::find($id);
        if(!$shift) {
            return response()->json([
                'code' => 404,
                'message' => 'Сотрудник не в смене!'
            ], 404);
        }

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Display orders taken in shift.
     */
    public function index($id): JsonResponse
    {
        // Выводим заказы только со статусом "принят"
        $orders = $this->service->index();
        $shift = Shift::where('id', $id);
        if(!$shift->first()) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой смены не существует!'
            ], 404);
        }
        if(!$orders->first()) {
            return response()->json([
                'code' => 404,
                'message' => 'Принятых заказов нет'
            ], 404);
        }

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Show order.
     */
    public function show($id): JsonResponse
    {
       // Выводим только один заказ по id если он присутствует
        $order = $this->service->show($id);
        if(!$order) {
            return response()->json([
                'code' => 404,
                'message' => 'Такого заказа нет!'
            ], 404);
        }

        return response()->json([
            'data' => $order
        ]);
    }

    /**
     * Add order in shift.
     */
    public function add_order(OrderRequest $request): JsonResponse
    {
       // Добавление заказа тем работником в случае если он присутствует на смене
        $work_shift = Shift::find($request['work_shift_id']);
        if(!$work_shift) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой смены не существует!'
            ], 404);
        }
        // Перебор всех сотрудников и проверка присутствуют ли он в смене
        $workers = $work_shift->users;
        foreach ($workers as $worker) {
            if($worker->role_id === Auth::user()->role_id) {
                // добавление заказа через сервис и его вывод после создания
                $order = $this->service->add_order($request);
                return response()->json([
                    'data' => $order,
                ]);
            }
        }
        return response()->json([
            'code' => 404,
            'message' => 'Сотрудника нет в смене!!'
        ], 404);
    }

    /**
     * Add position in order.
     */
    public function add_position($id, PositionRequest $request): JsonResponse
    {
        // Добавление позиции в заказ и проверки существует ли заказ и позиция меню
        $position = Position::find($request['menu_id']);
        $order = Order::find($id);
        $price_all = $order->price_all;
        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => 'Такого заказа нет!'
            ], 404);
        }
        if(!$position) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой позиции нет в меню!'
            ], 404);
        }
        // Подсчет итоговой стоиости заказа после добавления позиции
        $order->update([
           'price_all' => ($price_all + ($position->price * $position->count))
        ]);
        $order = $this->service->add_position($id, $request);

        return response()->json([
            'data' => $order
        ]);
    }

    /**
     * Delete position in order.
     */
    public function delete_position($id, $position_id): JsonResponse
    {
        if(!Order::find($id)) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого заказа не существует!'
                ]
            ], 404);
        }
        if(!OrderPosition::where('position_id', $position_id)->first()) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой позиции в заказе нет!'
                ]
            ], 404);
        }
        // удаление позиции из заказа и уменьшение итоговой стоимости после удаления
        $position = Position::find($position_id);
        $price_all = Order::find($id)->price_all - $position->price;
        Order::find($id)->update([
            'price_all' => $price_all
        ]);
        // все происходит через написанные сервисы
        $this->service->delete_position($id, $position_id);

        return response()->json([
            'message' => 'Позиция удалена!'
        ]);
    }

    /**
     * Change status order.
     */
    public function change_status($id, StatusRequest $request): JsonResponse
    {
    // Изменение статуса заказа при определенных условиях
        if(!Order::find($id)) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого заказа не существует!'
                ]
            ], 404);
        }
        if($request['status'] != 'Отменен' && $request['status'] != 'Оплачен') {
            return response()->json([
               'error' => [
                   'code' => 404,
                   'message' => 'Неверный статус заказа!'
               ]
            ]);
        }
        $this->service->change_status($id, $request);

        return response()->json([
            'message' => 'Статус изменен на '.$request['status']
        ]);
    }

    /**
     * Change status order by cook.
     */
    public function change_status_cook($id, StatusRequest $request): JsonResponse
    {
    // Изменение статуса заказа для роли повара
        $order = Order::find($id);
        if(!$order) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого заказа не существует!'
                ]
            ], 404);
        }
        if($order->status == 'Отменен') {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Данный заказ был отменен!'
                ]
            ]);
        }
        if($request['status'] !== 'Готовиться' && $order->status === 'Принят') {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Невозможно изменить статус заказа!'
                ]
            ]);
        }
        if($request['status'] !== 'Готов' && $order->status === 'Готовиться') {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Невозможно изменить статус заказа!'
                ]
            ]);
        }
        $this->service->change_status($id, $request);

        return response()->json([
            'message' => 'Статус изменен на '.$request['status']
        ]);
    }
```

- Контроллер пользователей

```php
php artisan make:controller User/ServiceController
```
```php
    // Создает поле для обращения к классу
    public UserService $service;
    // привязывает к полю созданный экземпляр класса UserService
    // после этого в контроллерах можно использовать методы привязанного сервиса
    public function __construct(UserService $service){
        $this->service = $service;
    }
```

```php
php artisan make:controller User/UserController
```
```php
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
    // вывод всех пользователей с помощью сервисов через коллекцию (ресурсы)
        $users = $this->service->index();

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request):JsonResponse
    {
    // создание сотрудника с помощью сервиса
        $user = $this->service->store($request);

        return response()->json([
            'id' => $user->id,
            'status' => 'Сотрудник заведен',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
    // отображение одного пользователя если он существует
        $user = $this->service->show($id);
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого пользователя не существует'
                ]
            ], 404);
        }

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
    // Увольнение сотрудника если он существует
        $user = $this->service->destroy($id);
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого пользователя не существует'
                ]
            ], 404);
        }

        return response()->json([
            'message' => 'Сотрудник уволен'
        ], 203);
    }
```

- Контроллер работников и смен

```php
php artisan make:controller WorkShift/ServiceController
```
```php
    // Создает поле для обращения к классу
    public ShiftService $service;
    // привязывает к полю созданный экземпляр класса UserService
    // после этого в контроллерах можно использовать методы привязанного сервиса
    public function __construct(ShiftService $service){
        $this->service = $service;
    }
```

```php
php artisan make:controller WorkShift/ShiftController
```
```php
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
    // Вывод сотрудников и смен с помощью сервиса и вывод их через ресурсы
        $work_shifts = $this->service->index();

        return response()->json([
            'data' => ShiftResource::collection($work_shifts),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShiftRequest $request):JsonResponse
    {
    // Добавление смены в случае соблюдения условий даты
        $work_shift = $this->service->store($request);
        if ($work_shift->end <= $work_shift->start) {
            return response()->json([
                'code' => 403,
                'message' => 'Неверные временные рамки!'
            ], 403);
        }

        return response()->json([
            'id' => $work_shift->id,
            'start' => $work_shift->start,
            'end' => $work_shift->end,
        ], 201);
    }

    /**
     * Open shift.
     */
    public function open($id):JsonResponse
    {
        // Открытие смены
        $work_shift = $this->service->open($id);

        return response()->json([
            'data' => $work_shift
        ]);
    }

    /**
     * Close shift.
     */
    public function close($id):JsonResponse
    {
        // Закрытие смены
        $work_shift = $this->service->close($id);

        return response()->json([
            'data' => $work_shift
        ]);
    }

    /**
     * Add user to shift.
     */
    public function add($id, addUserRequest $request):JsonResponse
    {
    // Добавление сотрудника в случае если существует смена и 
    // данный пользователь не находится в смене
        $shift = Shift::find($id);
        if (!$shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены не существует!'
                ]
            ], 404);
        }
        if($shift->users->where('id', $request['user_id'])->first()) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой пользователь уже в смене!'
                ]
            ], 404);
        }
        $user = User::find($request['user_id']);
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого пользователя не существует!'
                ]
            ], 404);
        }
        $this->service->add($id, $request);

        $user->update(['status' => 'working']);
        return response()->json([
            'id_user' => $request['user_id'],
            'status' => 'Сотрудник на смене'
        ]);
    }

    /**
     * Delete user to shift.
     */
    public function delete($id, $user_id):JsonResponse
    {
    // Удаление пользователя из смены в случае если он находится на смене
        $shift = Shift::find($id);
        if (!$shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены нет!'
                ]
            ], 404);
        }
        $user = UserShift::where('user_id', $user_id)->first();
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой пользователь не в смене!'
                ]
            ], 404);
        }
        $this->service->delete($id, $user_id);
        $user->update(['status' => 'not working']);

        return response()->json([
            'message' => 'Сотрудник удален из смены'
        ]);
    }
```
По итогу последним шагом необходимо прописать все роуты в `routes/api.php`
Переходим и прописываем следующее:

```php
// Общедоступные роуты
Route::post('/login', [AuthController::class, 'logIn'])->name('login');
Route::get('/unauthorization', [AuthController::class, 'unauthorization'])->name('unauthorization');

/**
 *  GROUP_AUTH
 */
 
 // Роуты которые доступны только авторизованным пользователям
Route::group(['middleware' => ['authorize', 'auth:sanctum']], function () {
    Route::get('/logout', [AuthController::class, 'logOut']);
});

/**
 *  GROUP_ADMIN_AUTH
*/

// Роуты доступные только авторизованным пользователям с ролью "администратор"
Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
    // Операции_сотрудники
    Route::get('/user', [UserController::class, 'index']);
    Route::post('/user', [UserController::class, 'store']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::get('/user/{id}/to-dismiss', [UserController::class, 'destroy']);
    // Операции_смены
    Route::post('/work-shift', [ShiftController::class, 'store']);
    Route::get('/work-shift/{id}/open', [ShiftController::class, 'open']);
    Route::get('/work-shift/{id}/close', [ShiftController::class, 'close']);
    Route::get('/work-shift', [ShiftController::class, 'index']);
    Route::post('/work-shift/{id}/user', [ShiftController::class, 'add']);
    Route::delete('/work-shift/{id}/user/{user_id}', [ShiftController::class, 'delete']);
    // Операции_заказы
    Route::get('/work-shift/{id}/order', [OrderController::class, 'orders']);
});

/**
 *  GROUP_WAITER_AUTH
 */
 
 // Роуты доступные только авторизованным пользователям с ролью "официант"
Route::group(['middleware' => ['auth:sanctum', 'waiter']], function () {
    Route::post('/order', [OrderController::class, 'add_order']);
    Route::get('/order/{id}', [OrderController::class, 'show']);
    Route::post('/order/{id}/position', [OrderController::class, 'add_position']);
    Route::delete('/order/{id}/position/{position_id}', [OrderController::class, 'delete_position']);
    Route::patch('/order/{id}/change-status', [OrderController::class, 'change_status']);
    Route::get('/work-shift/{id}/orders', [OrderController::class, 'index']);
});

/**
 *  GROUP_COOK_AUTH
 */
 
 // Роуты доступные только авторизованным пользователям с ролью "повар"
Route::group(['middleware' => ['auth:sanctum', 'cook']], function () {
    Route::get('/order/taken/get', [OrderController::class, 'taken']);
    Route::patch('/orders/{id}/change-status', [OrderController::class, 'change_status_cook']);
});
```

Теперь можно обращаться к определенным частям бизнес-логики серверной части и получать ответ к запросу
#### В случае возникновения каких либо ошибок, можно обратиться к разработчику данной документации и проекта. Да прибудет с вами сила!

## Лицензия [MIT license](https://opensource.org/licenses/MIT).
