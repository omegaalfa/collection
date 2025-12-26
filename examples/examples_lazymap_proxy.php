<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Omegaalfa\Collection\LazyMap;

echo "=== Demonstração: LazyMap + LazyProxyObject ===\n\n";

// Classe de exemplo que simula operação cara
class DatabaseUser
{
    private static int $instanceCount = 0;
    
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email
    ) {
        self::$instanceCount++;
        echo "   🔨 Criando DatabaseUser: {$this->name} (instância #{" . self::$instanceCount . "})\n";
        usleep(100000); // Simula query lenta (100ms)
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public static function getInstanceCount(): int
    {
        return self::$instanceCount;
    }
    
    public static function resetCount(): void
    {
        self::$instanceCount = 0;
    }
}

echo "1. LazyMap tradicional (closures manuais):\n";
echo "   Criando mapa com 3 usuários...\n\n";

DatabaseUser::resetCount();
$start = microtime(true);

$lazyMapTraditional = LazyMap::of(
    ['user1', fn() => new DatabaseUser('1', 'John Doe', 'john@example.com')],
    ['user2', fn() => new DatabaseUser('2', 'Jane Smith', 'jane@example.com')],
    ['user3', fn() => new DatabaseUser('3', 'Bob Johnson', 'bob@example.com')],
);

$creationTime = microtime(true) - $start;
echo "   ✅ Mapa criado em " . round($creationTime * 1000, 2) . "ms\n";
echo "   📊 Usuários instanciados: " . DatabaseUser::getInstanceCount() . "/3\n\n";

echo "   Acessando user1...\n";
$start = microtime(true);
$user1 = $lazyMapTraditional->get('user1');
$accessTime = microtime(true) - $start;
echo "   ✅ Nome: {$user1->getName()} (levou " . round($accessTime * 1000, 2) . "ms)\n";
echo "   📊 Usuários instanciados: " . DatabaseUser::getInstanceCount() . "/3\n\n";

echo "   Acessando user1 novamente (deve usar cache)...\n";
$start = microtime(true);
$user1Again = $lazyMapTraditional->get('user1');
$accessTime = microtime(true) - $start;
echo "   ✅ Nome: {$user1Again->getName()} (levou " . round($accessTime * 1000, 2) . "ms)\n";
echo "   📊 Usuários instanciados: " . DatabaseUser::getInstanceCount() . "/3\n\n";

echo "---\n\n";

echo "2. LazyMap + LazyProxyObject (PHP 8.4 native):\n";
echo "   Criando mapa com 3 usuários...\n\n";

DatabaseUser::resetCount();
$start = microtime(true);

$lazyMapProxy = LazyMap::ofLazyObjects([
    'user1' => [DatabaseUser::class, '1', 'John Doe', 'john@example.com'],
    'user2' => [DatabaseUser::class, '2', 'Jane Smith', 'jane@example.com'],
    'user3' => [DatabaseUser::class, '3', 'Bob Johnson', 'bob@example.com'],
]);

$creationTime = microtime(true) - $start;
echo "   ✅ Mapa criado em " . round($creationTime * 1000, 2) . "ms\n";
echo "   📊 Usuários instanciados: " . DatabaseUser::getInstanceCount() . "/3\n\n";

echo "   Acessando user1...\n";
$start = microtime(true);
$user1Proxy = $lazyMapProxy->get('user1');
$accessTime = microtime(true) - $start;
echo "   ✅ Nome: {$user1Proxy->getName()} (levou " . round($accessTime * 1000, 2) . "ms)\n";
echo "   📊 Usuários instanciados: " . DatabaseUser::getInstanceCount() . "/3\n\n";

echo "   Acessando user1 novamente...\n";
$start = microtime(true);
$user1ProxyAgain = $lazyMapProxy->get('user1');
$accessTime = microtime(true) - $start;
echo "   ✅ Nome: {$user1ProxyAgain->getName()} (levou " . round($accessTime * 1000, 2) . "ms)\n";
echo "   📊 Usuários instanciados: " . DatabaseUser::getInstanceCount() . "/3\n\n";

echo "---\n\n";

echo "3. LazyMap + Custom Factories:\n";
echo "   Para casos complexos de instanciação...\n\n";

DatabaseUser::resetCount();

$lazyMapFactories = LazyMap::ofLazyFactories([
    'cached' => [
        DatabaseUser::class, 
        fn() => new DatabaseUser('cache1', 'Cached User', 'cached@example.com')
    ],
    'fromDb' => [
        DatabaseUser::class,
        function() {
            echo "   🔍 Buscando do banco de dados...\n";
            return new DatabaseUser('db1', 'DB User', 'db@example.com');
        }
    ],
]);

echo "   Acessando usuário 'fromDb'...\n";
$dbUser = $lazyMapFactories->get('fromDb');
echo "   ✅ Nome: {$dbUser->getName()}\n";
echo "   📊 Usuários instanciados: " . DatabaseUser::getInstanceCount() . "/2\n\n";

echo "---\n\n";

echo "4. Comparação de Performance:\n\n";

// Eager vs Lazy
echo "   4.1. Criando 100 usuários EAGER:\n";
DatabaseUser::resetCount();
$start = microtime(true);
$memStart = memory_get_usage();

$eagerUsers = [];
for ($i = 1; $i <= 100; $i++) {
    $eagerUsers["user{$i}"] = new DatabaseUser(
        (string)$i,
        "User {$i}",
        "user{$i}@example.com"
    );
}

$memEnd = memory_get_usage();
$eagerTime = microtime(true) - $start;
$eagerInstances = DatabaseUser::getInstanceCount();

echo "   ⏱️  Tempo: " . round($eagerTime * 1000) . "ms\n";
echo "   💾 Memória: " . round(($memEnd - $memStart) / 1024, 2) . "KB\n";
echo "   📊 Instâncias: {$eagerInstances}/100\n\n";

echo "   4.2. Criando 100 usuários LAZY:\n";
DatabaseUser::resetCount();
$start = microtime(true);
$memStart = memory_get_usage();

$lazySpecs = [];
for ($i = 1; $i <= 100; $i++) {
    $lazySpecs["user{$i}"] = [DatabaseUser::class, (string)$i, "User {$i}", "user{$i}@example.com"];
}
$lazyUsers = LazyMap::ofLazyObjects($lazySpecs);

$memEnd = memory_get_usage();
$lazyTime = microtime(true) - $start;
$lazyInstances = DatabaseUser::getInstanceCount();

echo "   ⏱️  Tempo: " . round($lazyTime * 1000) . "ms\n";
echo "   💾 Memória: " . round(($memEnd - $memStart) / 1024, 2) . "KB\n";
echo "   📊 Instâncias: {$lazyInstances}/100\n\n";

echo "   4.3. Acessando apenas 5 usuários lazy:\n";
$start = microtime(true);
for ($i = 1; $i <= 5; $i++) {
    $user = $lazyUsers->get("user{$i}");
    $user->getName();
}
$accessTime = microtime(true) - $start;
$finalInstances = DatabaseUser::getInstanceCount();

echo "   ⏱️  Tempo de acesso: " . round($accessTime * 1000) . "ms\n";
echo "   📊 Instâncias finais: {$finalInstances}/100\n\n";

echo "   📈 Comparação:\n";
echo "   - Tempo criação: Lazy é " . round($eagerTime / $lazyTime, 1) . "x mais rápido\n";
echo "   - Instâncias criadas: " . $eagerInstances . " (eager) vs " . $finalInstances . " (lazy)\n";
echo "   - Economia: " . round((1 - $finalInstances / $eagerInstances) * 100, 1) . "% menos instâncias\n\n";

echo "===  Benefícios do LazyMap + LazyProxyObject ===\n";
echo "✅ Instanciação sob demanda (lazy loading)\n";
echo "✅ Performance nativa PHP 8.4+\n";
echo "✅ Cache automático de objetos\n";
echo "✅ Economia massiva de memória e CPU\n";
echo "✅ Perfeito para DB queries, API calls, objetos pesados\n";
echo "✅ Fallback automático para closures em PHP < 8.4\n";
