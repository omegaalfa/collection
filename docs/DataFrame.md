# 📊 DataFrame — Guia de Uso

> Abstração tabular estilo pandas sobre a arquitetura de collections e file parsers da biblioteca.

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue)](https://php.net)
[![Lazy](https://img.shields.io/badge/Evaluation-Lazy-brightgreen)](#-avalia%C3%A7%C3%A3o-lazy)
[![Type Safety](https://img.shields.io/badge/Type%20Safety-Generics-green)](https://phpstan.org)

---

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [Factory Constructors](#-factory-constructors)
  - [DataFrame::fromArray()](#dataframefromarrayarray-data-self)
  - [DataFrame::fromCsv()](#dataframefromcsvstring-filepath-array-options--self)
  - [DataFrame::fromJsonLines()](#dataframefromjsonlinesstring-filepath-self)
  - [DataFrame::fromSequence()](#dataframefromsequencesequenceinterface-sequence-self)
- [Seleção e Filtragem](#-seleção-e-filtragem)
  - [select()](#selectstring-columns-self)
  - [where()](#wherecallable-predicate-self)
  - [head()](#headint-n--5-array)
  - [tail()](#tailint-n--5-array)
- [Operações com Colunas](#-operações-com-colunas)
  - [withColumn()](#withcolumnstring-name-callable-callback-self)
  - [drop()](#dropstring-columns-self)
  - [rename()](#renamearray-mapping-self)
  - [columns()](#columns-array)
- [Group By e Agregações](#-group-by-e-agregações)
  - [groupBy()](#groupbystring-column-array)
  - [count()](#count-int)
  - [sum()](#sumstring-column-floatint)
  - [mean()](#meanstring-column-float)
  - [min() / max()](#minstring-column--maxstring-column-mixed)
  - [describe()](#describestring-column-array)
  - [cached()](#cached-self)
- [Exportação](#-exportação)
  - [toArray()](#toarray-array)
  - [toCsv()](#tocsvstring-filepath-void)
  - [toJson()](#tojson-string)
- [Avaliação Lazy](#-avaliação-lazy)
- [Tratamento de Erros](#-tratamento-de-erros)
- [Exemplo Completo](#-exemplo-completo)
- [Integração com a Biblioteca](#-integração-com-a-biblioteca)
- [Limitações Conhecidas](#-limitações-conhecidas)

---

## 🌐 Visão Geral

Cada **linha** é um array associativo `array<string, mixed>` (coluna => valor) e cada **coluna** é uma chave compartilhada entre as linhas:

```php
use Omegaalfa\Collection\DataFrame;

$df = DataFrame::fromArray([
    ['name' => 'alice', 'dept' => 'eng', 'salary' => 100],
    ['name' => 'bob',   'dept' => 'eng', 'salary' => 200],
    ['name' => 'carol', 'dept' => 'hr',  'salary' => 300],
]);

$df->columns(); // ['name', 'dept', 'salary']
$df->count();   // 3
```

**Modelo mental:**

| Conceito | Comportamento |
|---|---|
| Linha | `array<string, mixed>` — registro tabular |
| Coluna | chave da linha; lista obtida com `columns()` |
| Imutável | toda transformação retorna um **novo** DataFrame |
| Lazy | `select`/`where`/`withColumn`/`drop`/`rename` **não avaliam** nada até um terminal |
| Terminal | `toArray`/`head`/`tail`/`groupBy`/agregações/`toCsv`/`toJson` avaliam a cadeia |
| Schema | conhecido (da fonte/transformações) ou inferido da **primeira linha** em fontes lazy |

**Reuso da arquitetura:** as leituras de arquivo delegam para [`LazyFileIterator`](LazyFileIterator_README.md) + `FileParser\*Parser` (CSV/TSV/JSONL) e as cadeias usam [`LazySequence`](API.md#-lazysequence) internamente — streaming com memória constante.

---

## 🏗️ Factory Constructors

### `DataFrame::fromArray(array $data): self`

Cria um DataFrame a partir de uma lista de linhas associativas. As colunas são a **união** das chaves de todas as linhas, em ordem de primeira aparição (linhas irregulares recebem `null` nas chaves ausentes).

```php
$df = DataFrame::fromArray([
    ['name' => 'alice', 'salary' => 100],
    ['name' => 'bob',   'salary' => 200],
]);

$df->columns(); // ['name', 'salary']
$df->count();   // 2
```

Linhas com colunas extras são absorvidas no schema:

```php
$df = DataFrame::fromArray([
    ['a' => 1],
    ['b' => 2],
]);

$df->columns(); // ['a', 'b']
```

> ⚠️ Cada elemento **deve** ser um array; caso contrário: `InvalidArgumentException`.
> As chaves externas do array são descartadas — as linhas são posicionais (ordem preservada).

---

### `DataFrame::fromCsv(string $filePath, array $options = []): self`

Lê um CSV de forma **lazy** (streaming via `LazyFileIterator::csv()`). O arquivo não é carregado na memória por inteiro.

```php
// arquivo.csv:
// id,name,dept,salary
// 1,alice,eng,100
// 2,bob,eng,200
// 3,carol,hr,300

$df = DataFrame::fromCsv('arquivo.csv');

$df->columns(); // ['id', 'name', 'dept', 'salary']  (inferido da primeira linha de dados)
$df->head(2);   // avalia no máximo 2 linhas do arquivo
```

**Opções (`$options`):**

| Chave | Tipo | Padrão | Descrição |
|---|---|---|---|
| `delimiter` | `string` | `','` | Separador de campos |
| `enclosure` | `string` | `'"'` | Caractere de aspas |
| `escape` | `string` | `'\\'` | Caractere de escape |
| `hasHeaders` | `bool` | `true` | Primeira linha é o cabeçalho (descartada da leitura) |
| `columns` | `list<string>` | — | Nomina colunas posicionais ou renomeia o header na leitura |

**CSV sem header, com nomes de coluna e outro delimitador:**

```php
// arquivo.csv:
// 1;alice
// 2;bob

$df = DataFrame::fromCsv('arquivo.csv', [
    'delimiter'   => ';',
    'hasHeaders'  => false,
    'columns'     => ['id', 'name'],
]);

$df->toArray();
// [
//     ['id' => '1', 'name' => 'alice'],
//     ['id' => '2', 'name' => 'bob'],
// ]
```

**Renomear colunas na leitura** (equivalente ao `names` do pandas — os nomes substituem o header, na ordem das colunas):

```php
// arquivo.csv:
// id,name
// 1,alice

$df = DataFrame::fromCsv('arquivo.csv', ['columns' => ['identifier', 'label']]);

$df->columns(); // ['identifier', 'label']
$df->toArray(); // [['identifier' => '1', 'label' => 'alice']]
```

> 📌 **CSV devolve strings.** `"1"` ≠ `1`. Use `withColumn` para converter tipos.

---

### `DataFrame::fromJsonLines(string $filePath): self`

Lê um arquivo JSON Lines (um objeto JSON por linha) de forma lazy via `LazyFileIterator::jsonLines()`.

```php
// arquivo.jsonl:
// {"id": 1, "tags": ["a", "b"]}
// {"id": 2, "tags": []}

$df = DataFrame::fromJsonLines('arquivo.jsonl');

$df->toArray();
// [
//     ['id' => 1, 'tags' => ['a', 'b']],
//     ['id' => 2, 'tags' => []],
// ]
```

> 📌 Diferente do CSV, JSON preserva tipos (`1` é int, `["a"]` é array). Linhas que não são objetos JSON (ex.: `42`, `"texto"`) são rejeitadas com `InvalidArgumentException` — JSONL tabular espera objetos.

---

### `DataFrame::fromSequence(SequenceInterface $sequence): self`

Cria um DataFrame a partir de uma [`Sequence`](API.md#-sequence) (ou qualquer `SequenceInterface`) de linhas.

```php
use Omegaalfa\Collection\Sequence;

$df = DataFrame::fromSequence(Sequence::of(
    ['x' => 1],
    ['x' => 2],
    ['x' => 3],
));

$df->sum('x'); // 6
```

---

## 🔍 Seleção e Filtragem

### `select(string ...$columns): self`

Projeta colunas, preservando a ordem informada. **Lazy.**

```php
$df = DataFrame::fromArray([
    ['name' => 'alice', 'dept' => 'eng', 'salary' => 100],
    ['name' => 'bob',   'dept' => 'eng', 'salary' => 200],
]);

$df->select('salary', 'name')->toArray();
// [
//     ['salary' => 100, 'name' => 'alice'],
//     ['salary' => 200, 'name' => 'bob'],
// ]
```

**Regras:**

- Pelo menos uma coluna é obrigatória (`select()` vazio → `InvalidArgumentException`).
- **Schema conhecido** (ex.: `fromArray`): coluna inexistente → `InvalidArgumentException` (proteção contra typo).
- **Schema desconhecido** (fonte lazy sem `columns`): colunas ausentes são preenchidas com `null`.

```php
// arquivo.csv com apenas a coluna "a" — schema inferido da primeira linha
$df->select('a', 'b')->toArray();
// [['a' => '1', 'b' => null]]   ← 'b' preenchida com null
```

---

### `where(callable $predicate): self`

Filtra linhas com um predicado `fn(array $row): bool`. **Lazy** — a fonte só é consumida até achar o suficiente para o terminal.

```php
$df->where(fn(array $row): bool => $row['dept'] === 'eng')->toArray();
// [
//     ['name' => 'alice', 'dept' => 'eng', 'salary' => 100],
//     ['name' => 'bob',   'dept' => 'eng', 'salary' => 200],
// ]
```

Encadeando com `head()` para parar cedo:

```php
$result = DataFrame::fromCsv('vendas.csv')          // 1 milhão de linhas
    ->where(fn(array $row): bool => (int)$row['qty'] > 100)
    ->select('id', 'qty')
    ->head(3);                                      // avalia ~linhas até achar 3 matches
```

---

### `head(int $n = 5): array`

Retorna as **primeiras N linhas** (`list<array<string, mixed>>`). Avalia no máximo N linhas da fonte. `n <= 0` retorna `[]`. Com menos linhas que N, retorna todas.

```php
$df->head(2);
// [
//     ['name' => 'alice', 'dept' => 'eng', 'salary' => 100],
//     ['name' => 'bob',   'dept' => 'eng', 'salary' => 200],
// ]

$df->head();    // até 5 linhas (padrão)
$df->head(0);   // []
```

---

### `tail(int $n = 5): array`

Retorna as **últimas N linhas**. Usa uma janela O(n) de memória — não materializa o total.

```php
$df->tail(2);
// [
//     ['name' => 'bob',   'dept' => 'eng', 'salary' => 200],
//     ['name' => 'carol', 'dept' => 'hr',  'salary' => 300],
// ]

$df->tail(0);   // []
```

> 📌 `head()`/`tail()` retornam **array** (terminal de conveniência). Para continuar a cadeia, use `take()` implícito via `where`/`select` e um terminal maior, ou materialize com `toArray()`.

---

## 🗂️ Operações com Colunas

### `withColumn(string $name, callable $callback): self`

Adiciona ou substitui uma coluna. O callback recebe a linha inteira: `fn(array $row): mixed`. **Lazy.**

```php
$df->withColumn('bonus', fn(array $row): int => $row['salary'] * 2)->toArray();
// [
//     ['name' => 'alice', 'dept' => 'eng', 'salary' => 100, 'bonus' => 200],
//     ['name' => 'bob',   'dept' => 'eng', 'salary' => 200, 'bonus' => 400],
//     ...
// ]
```

Coluna nova vai ao **fim**; coluna existente mantém a posição:

```php
$df->withColumn('salary', fn(array $row): int => $row['salary'] + 1);

$df->columns(); // ['name', 'dept', 'salary', 'bonus']  (salary substituída no lugar)
```

Conversão de tipos vindos de CSV (que sempre são string):

```php
DataFrame::fromCsv('arquivo.csv')
    ->withColumn('salary', fn(array $row): float => (float)$row['salary']);
```

> ⚠️ Nome vazio → `InvalidArgumentException`.

---

### `drop(string ...$columns): self`

Remove colunas. **Lazy.** Colunas informadas que não existem são ignoradas sem erro.

```php
$df->drop('dept', 'salary')->toArray();
// [['name' => 'alice'], ['name' => 'bob'], ['name' => 'carol']]

$df->drop('dept', 'salary')->columns(); // ['name']
```

---

### `rename(array $mapping): self`

Renomeia colunas no formato `[antigo => novo]`. **Lazy.** Colunas fora do mapping ficam como estão; renomear uma coluna inexistente é um no-op.

```php
$df->rename(['salary' => 'pay'])->toArray()[0];
// ['name' => 'alice', 'dept' => 'eng', 'pay' => 100]

$df->rename(['salary' => 'pay'])->columns(); // ['name', 'dept', 'pay']
```

> ⚠️ Novo nome vazio → `InvalidArgumentException`.

---

### `columns(): array`

Lista as colunas como `list<string>`.

```php
$df->columns(); // ['name', 'dept', 'salary']
```

- **Schema conhecido** (`fromArray`, `fromCsv` com `columns`, após `select`/`withColumn`/`drop`/`rename`): retorno direto.
- **Fontes lazy**: inferido das chaves da **primeira linha de dados** (ler o header não conta — ele é consumido pelo parser). Frame vazio → `[]`.

---

## 📈 Group By e Agregações

### `groupBy(string $column): array`

Agrupa linhas pelo valor de uma coluna e retorna `array<array-key, DataFrame>` — cada grupo é um DataFrame completo (com as mesmas colunas). A ordem dos grupos segue a **primeira aparição**. **Terminal** (materializa as linhas).

```php
$groups = $df->groupBy('dept');

array_keys($groups);   // ['eng', 'hr']
count($groups['eng']); // 2
count($groups['hr']);  // 1

$groups['hr']->toArray();
// [['name' => 'carol', 'dept' => 'hr', 'salary' => 300]]
```

Agregando por grupo:

```php
$salaryByDept = [];
foreach ($df->groupBy('dept') as $dept => $group) {
    $salaryByDept[$dept] = $group->sum('salary');
}
// ['eng' => 300, 'hr' => 300]
```

**Normalização de chaves** (as chaves de array PHP são `int|string`):

| Valor da coluna | Chave do grupo |
|---|---|
| `int` / `string` | preservada |
| `true` / `false` | `'1'` / `'0'` |
| `float` | string (`(string)$valor`) |
| `null` | `''` |
| `array` / `object` | ⚠️ `InvalidArgumentException` |

---

### `count(): int`

Número de linhas (também disponível via `count($df)`, pois implementa `Countable`).

```php
$df->count();     // 3
count($df);       // 3
```

---

### `sum(string $column): float|int`

Soma os valores **numéricos** da coluna; não numéricos e `null` contam como 0 (sem warning). Coluna vazia/só não numéricos → `0`.

```php
$df->sum('salary'); // 600
```

---

### `mean(string $column): float`

Média dos valores **numéricos** da coluna (não numéricos são ignorados no numerador **e** no denominador).

```php
$df->mean('salary'); // 200.0
```

Média só dos valores numéricos:

```php
DataFrame::fromArray([
    ['v' => 1],
    ['v' => 'x'],   // ignorado
    ['v' => 2.5],
])->mean('v');      // 1.75  (média de 1 e 2.5)
```

> ⚠️ Coluna **sem nenhum valor numérico** → `InvalidArgumentException` (a assinatura exige `float`).

---

### `min(string $column): mixed` / `max(string $column): mixed`

Menor/maior valor da coluna. `null`s são ignorados; coluna vazia (ou só `null`) retorna `null`. A comparação usa a semântica nativa de `<`/`>` do PHP (strings comparam alfabeticamente).

```php
$df->min('salary'); // 100
$df->max('salary'); // 300
$df->min('name');   // 'alice'
$df->max('name');   // 'carol'
```

---

### `describe(string $column): array`

Calcula `count`, `sum`, `mean`, `min` e `max` dos valores numéricos em uma única passagem pela fonte. Valores não numéricos são ignorados.

```php
$df->describe('salary');
// [
//     'count' => 3,
//     'sum' => 600,
//     'mean' => 200.0,
//     'min' => 100,
//     'max' => 300,
// ]
```

Em uma coluna sem valores numéricos, `mean` retorna `null`.

---

### `cached(): self`

Materializa as linhas uma vez e devolve um novo DataFrame reutilizável. É útil quando vários terminais serão executados sobre um CSV ou outra fonte lazy; o custo é trocar I/O repetido por memória proporcional ao número de linhas.

```php
$cached = DataFrame::fromCsv('vendas.csv')->cached();

$total = $cached->sum('salary');
$average = $cached->mean('salary');
$count = $cached->count();
```

---

## 💾 Exportação

### `toArray(): array`

Materializa tudo em `list<array<string, mixed>>`.

```php
$df->toArray();
// [
//     ['name' => 'alice', 'dept' => 'eng', 'salary' => 100],
//     ['name' => 'bob',   'dept' => 'eng', 'salary' => 200],
//     ['name' => 'carol', 'dept' => 'hr',  'salary' => 300],
// ]
```

---

### `toCsv(string $filePath): void`

Exporta para CSV com header = `columns()`.

```php
$df->select('name', 'salary')->toCsv('saida.csv');
```

```
name,salary
alice,100
bob,200
carol,300
```

Conversão de células: `null` → `''`, `bool` → `'1'`/`'0'`, escalares → string, arrays/objetos → JSON.

> ⚠️ Tipagem não sobrevive ao CSV: um roundtrip `toCsv` → `fromCsv` devolve strings (`'100'`, não `100`).

---

### `toJson(): string`

Serializa as linhas como array JSON (com `JSON_THROW_ON_ERROR`).

```php
$df->select('name', 'salary')->toJson();
// [{"name":"alice","salary":100},{"name":"bob","salary":200},{"name":"carol","salary":300}]
```

---

## ⚡ Avaliação Lazy

Transformações constroem um **pipeline de estágios** sobre `LazySequence`; nada toca na fonte até um terminal.

| Método | Avaliação |
|---|---|
| `select`, `where`, `withColumn`, `drop`, `rename` | 🟢 Lazy (só descrevem o pipeline) |
| `head`, `toArray`, `count`, `sum`, `mean`, `min`, `max`, `toCsv`, `toJson` | 🔴 Terminal |
| `tail`, `groupBy` | 🔴 Terminal (janela O(n) / materializa) |

`describe()` também é terminal, mas combina as cinco agregações em uma única passagem. Para múltiplos terminais independentes, use `cached()` quando o custo de memória for aceitável.

Isso mantém a memória constante em fluxos grandes:

```php
// 20.000 linhas no arquivo — head(5) não materializa o total
$mem = memory_get_usage();

DataFrame::fromCsv('grande.csv')
    ->where(fn(array $row): bool => true)
    ->select('id')
    ->head(5);

$delta = memory_get_usage() - $mem; // ~KBs, não dezenas de MB
```

Encadeie livremente — a ordem das transformações define a ordem dos estágios:

```php
$result = DataFrame::fromCsv('vendas.csv')
    ->where(fn(array $row): bool => (int)$row['qty'] > 10)
    ->withColumn('total', fn(array $row): float => (int)$row['qty'] * (float)$row['price'])
    ->select('id', 'total')
    ->head(100);
```

---

## 🚨 Tratamento de Erros

Todos os erros de contrato lançam `InvalidArgumentException` (ou `RuntimeException`/`JsonException` de I/O/serialização):

| Exceção | Quando |
|---|---|
| `InvalidArgumentException` | `fromArray`/`fromSequence`/`fromJsonLines` com linha que não é array |
| `InvalidArgumentException` | `select()` sem colunas |
| `InvalidArgumentException` | `select`/`sum`/`mean`/`min`/`max`/`groupBy` em coluna inexistente **com schema conhecido** |
| `InvalidArgumentException` | `mean` em coluna sem valores numéricos |
| `InvalidArgumentException` | `withColumn`/`rename` com nome vazio |
| `InvalidArgumentException` | `groupBy` com valor não escalar (`array`/`object`) |
| `RuntimeException` | `toCsv` sem permissão/caminho inválido |
| `JsonException` | `toJson`/`toCsv` com valor não serializável |

```php
try {
    $df->select('coluna_que_nao_existe');
} catch (InvalidArgumentException $e) {
    // "Column 'coluna_que_nao_existe' not found"
}
```

---

## 🧩 Exemplo Completo

Pipeline ponta a ponta: CSV → filtro → coluna derivada → agregação por grupo → exportação.

```php
<?php

declare(strict_types=1);

use Omegaalfa\Collection\DataFrame;

// vendas.csv:
// id,region,qty,price
// 1,north,10,5.0
// 2,south,3,2.0
// 3,north,20,5.0
// 4,east,7,1.5

$df = DataFrame::fromCsv('vendas.csv')
    ->where(fn(array $row): bool => (int)$row['qty'] >= 7)
    ->withColumn('total', fn(array $row): float => (int)$row['qty'] * (float)$row['price']);

echo "Colunas: ", implode(', ', $df->columns()), "\n";
// Colunas: id, region, qty, price, total

echo "Registros: ", $df->count(), "\n";
// Registros: 3

echo "Receita total: ", $df->sum('total'), "\n";
// Receita total: 160.5

echo "Ticket médio: ", $df->mean('total'), "\n";
// Ticket médio: 53.5

foreach ($df->groupBy('region') as $region => $group) {
    echo "{$region}: ", $group->sum('total'), "\n";
}
// north: 150
// east: 10.5

// Exporta a visão resumida
$df->select('id', 'region', 'total')->toCsv('resumo.csv');
$df->select('id', 'total')->toJson();
// [{"id":"1","total":50},{"id":"3","total":100},{"id":"4","total":10.5}]
```

---

## 🔗 Integração com a Biblioteca

O DataFrame conversa com o resto da library pelas factories:

```php
use Omegaalfa\Collection\Collection;
use Omegaalfa\Collection\Sequence;
use Omegaalfa\Collection\LazySequence;
use Omegaalfa\Collection\LazyFileIterator;
use Omegaalfa\Collection\DataFrame;

// Collection → DataFrame
$rows = (new Collection($raw))->filter(fn($r) => $r['ativo'])->toArray();
$df = DataFrame::fromArray($rows);

// Sequence → DataFrame
$df = DataFrame::fromSequence(Sequence::of(['x' => 1], ['x' => 2]));

// LazyFileIterator + parser custom → DataFrame (via fromArray da materialização)
$df = DataFrame::fromArray(
    iterator_to_array(new LazyFileIterator('dados.txt', $parser), false)
);

// DataFrame → Sequence (para APIs que esperam SequenceInterface)
$seq = Sequence::from($df->toArray());
```

Para leitura de arquivo, prefira `fromCsv`/`fromJsonLines` — eles já encapsulam `LazyFileIterator` + parsers com streaming.

---

## ⚠️ Limitações Conhecidas

1. **CSV sempre devolve strings** — converta com `withColumn` (`(float)$row[...]`, `(int)$row[...]`).
2. **Schema de fontes lazy** vem da primeira linha de dados (o header CSV é consumido pelo parser). Arquivos vazios (só header) resultam em `columns() === []`; passe `columns` nas options se o schema importar.
3. **Linhas irregulares**: chaves ausentes leem como `null`; chaves extras de linhas posteriores não entram em `toCsv` (CSV é retangular).
4. **`groupBy` materializa** as linhas (agrupamento exige ver tudo) — use só após `where`/`select` para reduzir o volume.
5. **`mean` lança exceção** se a coluna não tiver nenhum valor numérico; `min`/`max` retornam `null` nesse caso.
6. **JSONL tabular espera objetos** por linha; escalares soltos (`42`, `"abc"`) não são linhas válidas.

---

<div align="center">

[⬆ Voltar ao topo](#-dataframe--guia-de-uso) · [API Reference](API.md) · [README](../README.md)

</div>
