<?php

namespace App\Service;

use RuntimeException;
use SimpleXMLElement;

class OneCAdvertisementXmlParser
{
    /**
     * @return array{
     *     sizes: array<string, array<string, mixed>>,
     *     sideTypes: array<string, array<string, mixed>>,
     *     types: array<string, array<string, mixed>>,
     *     advertisements: array<string, array<string, mixed>>
     * }
     */
    public function parse(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(sprintf('XML-файл "%s" не найден или недоступен для чтения.', $path));
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$xml instanceof SimpleXMLElement) {
            $message = $errors === [] ? 'неизвестная ошибка XML' : trim($errors[0]->message);
            throw new RuntimeException(sprintf('Не удалось прочитать XML-файл "%s": %s', $path, $message));
        }

        $sizes = [];
        $sideTypes = [];
        $types = [];
        $advertisements = [];

        $bodyNodes = $xml->xpath('//*[local-name()="Body"]');
        if ($bodyNodes === false || $bodyNodes === []) {
            throw new RuntimeException('В XML не найден блок v8msg:Body с данными 1С.');
        }

        $body = $bodyNodes[0];
        foreach ($body->children() as $node) {
            $name = $node->getName();

            if ($name === 'CatalogObject.Размеры') {
                $ref = $this->text($node, 'Ref');
                if ($ref !== null && $this->isActive($node)) {
                    $sizes[$ref] = [
                        'ref' => $ref,
                        'name' => $this->text($node, 'Description') ?? $ref,
                        'rawFields' => $this->fields($node),
                    ];
                }

                continue;
            }

            if ($name === 'CatalogObject.ВидыСторон') {
                $ref = $this->text($node, 'Ref');
                if ($ref !== null && $this->isActive($node)) {
                    $sideName = $this->normalizeSideCode($this->text($node, 'Description') ?? $this->text($node, 'PredefinedDataName') ?? $this->text($node, 'Code') ?? '');
                    if ($sideName !== null) {
                        $sideTypes[$ref] = [
                            'ref' => $ref,
                            'code' => $this->text($node, 'Code') ?? '',
                            'name' => $sideName,
                            'rawFields' => $this->fields($node),
                        ];
                    }
                }

                continue;
            }

            if ($name === 'CatalogObject.ТипыРекламныхБлоков') {
                $ref = $this->text($node, 'Ref');
                if ($ref !== null && $this->isActive($node)) {
                    $sizeRef = $this->text($node, 'Размер');
                    $types[$ref] = [
                        'ref' => $ref,
                        'code' => $this->text($node, 'Code') ?? '',
                        'name' => $this->text($node, 'Description') ?? $ref,
                        'carrier' => $this->emptyToNull($this->text($node, 'ВидНосителя')),
                        'constructionType' => $this->emptyToNull($this->text($node, 'ТипКонструкции')),
                        'sizeRef' => $sizeRef,
                        'sizeName' => $sizeRef !== null ? ($sizes[$sizeRef]['name'] ?? null) : null,
                        'rawFields' => $this->fields($node),
                    ];
                }
            }
        }

        foreach ($body->children() as $node) {
            if ($node->getName() !== 'CatalogObject.РекламныеБлоки' || !$this->isActive($node)) {
                continue;
            }

            $placeNumber = $this->extractPlaceNumber($node);
            if ($placeNumber === null) {
                continue;
            }

            $sideCode = $this->extractSideCode($node, $sideTypes);
            $typeRef = $this->emptyToNull($this->text($node, 'ТипБлока'));
            $address = $this->emptyToNull($this->text($node, 'Адрес') ?? $this->text($node, 'Adress'));
            $coordinates = $this->parseCoordinates($this->text($node, 'Координаты'));
            $description = $this->emptyToNull($this->text($node, 'Описание') ?? $this->text($node, 'Specification'));
            $image = $this->emptyToNull($this->text($node, 'ОсновноеИзображение'));
            $rawFields = $this->fields($node);

            $advertisements[$placeNumber] ??= [
                'placeNumber' => $placeNumber,
                'code' => $placeNumber,
                'externalRef' => $this->emptyToNull($this->text($node, 'Ref')),
                'address' => $address,
                'typeRef' => $typeRef,
                'typeName' => $typeRef !== null ? ($types[$typeRef]['name'] ?? null) : null,
                'coordinates' => $coordinates,
                'sides' => [],
                'sideDetails' => [],
                'sourceRecords' => [],
            ];

            $advertisements[$placeNumber]['sourceRecords'][] = $rawFields;

            if ($advertisements[$placeNumber]['address'] === null && $address !== null) {
                $advertisements[$placeNumber]['address'] = $address;
            }
            if ($advertisements[$placeNumber]['coordinates'] === null && $coordinates !== null) {
                $advertisements[$placeNumber]['coordinates'] = $coordinates;
            }
            if ($advertisements[$placeNumber]['typeRef'] === null && $typeRef !== null) {
                $advertisements[$placeNumber]['typeRef'] = $typeRef;
                $advertisements[$placeNumber]['typeName'] = $types[$typeRef]['name'] ?? null;
            }

            if ($sideCode !== null) {
                if (!in_array($sideCode, $advertisements[$placeNumber]['sides'], true)) {
                    $advertisements[$placeNumber]['sides'][] = $sideCode;
                }

                $advertisements[$placeNumber]['sideDetails'][$sideCode] = [
                    'code' => $sideCode,
                    'description' => $description,
                    'image' => $image,
                    'externalRef' => $this->emptyToNull($this->text($node, 'Ref')),
                    'rawFields' => $rawFields,
                ];
            }
        }

        foreach ($advertisements as &$advertisement) {
            $typeRef = $advertisement['typeRef'];
            if ($typeRef !== null && $advertisement['typeName'] === null && isset($types[$typeRef])) {
                $advertisement['typeName'] = $types[$typeRef]['name'];
            }
        }
        unset($advertisement);

        ksort($advertisements, SORT_NATURAL);

        return [
            'sizes' => $sizes,
            'sideTypes' => $sideTypes,
            'types' => $types,
            'advertisements' => $advertisements,
        ];
    }

    private function isActive(SimpleXMLElement $node): bool
    {
        return $this->text($node, 'DeletionMark') !== 'true' && $this->text($node, 'IsFolder') !== 'true';
    }

    /** @return array<string, mixed> */
    private function fields(SimpleXMLElement $node): array
    {
        $fields = [];
        foreach ($node->children() as $child) {
            $name = $child->getName();
            $entry = $this->fieldValue($child);

            if (!array_key_exists($name, $fields)) {
                $fields[$name] = $entry;
                continue;
            }

            if (!is_array($fields[$name]) || !array_key_exists(0, $fields[$name])) {
                $fields[$name] = [$fields[$name]];
            }

            $fields[$name][] = $entry;
        }

        return $fields;
    }

    /** @return mixed */
    private function fieldValue(SimpleXMLElement $node): mixed
    {
        $children = [];
        foreach ($node->children() as $child) {
            $name = $child->getName();
            $value = $this->fieldValue($child);

            if (!array_key_exists($name, $children)) {
                $children[$name] = $value;
                continue;
            }

            if (!is_array($children[$name]) || !array_key_exists(0, $children[$name])) {
                $children[$name] = [$children[$name]];
            }

            $children[$name][] = $value;
        }

        $attributes = [];
        foreach ($node->attributes() as $attributeName => $attributeValue) {
            $attributes[$attributeName] = (string) $attributeValue;
        }
        foreach ($node->attributes('http://www.w3.org/2001/XMLSchema-instance') as $attributeName => $attributeValue) {
            $attributes[$attributeName] = (string) $attributeValue;
        }

        if ($children === [] && $attributes === []) {
            return trim((string) $node);
        }

        $entry = [];
        if ($children === []) {
            $entry['value'] = trim((string) $node);
        } else {
            $entry['children'] = $children;
        }
        if ($attributes !== []) {
            $entry['attributes'] = $attributes;
        }

        return $entry;
    }

    private function text(SimpleXMLElement $node, string $childName): ?string
    {
        $first = null;
        foreach ($node->children() as $child) {
            if ($child->getName() !== $childName) {
                continue;
            }

            $value = trim((string) $child);
            $first ??= $value;
            if ($value !== '') {
                return $value;
            }
        }

        return $first;
    }

    private function extractPlaceNumber(SimpleXMLElement $node): ?string
    {
        $placeNumber = $this->emptyToNull($this->text($node, 'НомерБлока') ?? $this->text($node, 'Номер'));
        if ($placeNumber !== null) {
            return $placeNumber;
        }

        $description = $this->text($node, 'Description');
        if ($description !== null && preg_match('/^\s*([^,;]+)/u', $description, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /** @param array<string, array{ref: string, code: string, name: string}> $sideTypes */
    private function extractSideCode(SimpleXMLElement $node, array $sideTypes): ?string
    {
        $sideRef = $this->emptyToNull($this->text($node, 'Сторона'));
        if ($sideRef !== null) {
            return $sideTypes[$sideRef]['name'] ?? null;
        }

        $description = $this->text($node, 'Description');
        if ($description !== null) {
            $parts = array_map('trim', explode(',', $description));
            if (isset($parts[1])) {
                $code = $this->normalizeSideCode($parts[1]);

                return $this->looksLikeSideCode($code) ? $code : null;
            }
        }

        return null;
    }

    private function looksLikeSideCode(?string $code): bool
    {
        return $code !== null && preg_match('/^(?:[ABC]\d*|A,B,C)$/u', $code) === 1;
    }

    /** @return array{latitude: float, longitude: float}|null */
    private function parseCoordinates(?string $coordinates): ?array
    {
        $coordinates = $this->emptyToNull($coordinates);
        if ($coordinates === null) {
            return null;
        }

        if (preg_match('/(-?\d+(?:[\.,]\d+)?)\s*[,; ]\s*(-?\d+(?:[\.,]\d+)?)/u', $coordinates, $matches) !== 1) {
            return null;
        }

        return [
            'latitude' => (float) str_replace(',', '.', $matches[1]),
            'longitude' => (float) str_replace(',', '.', $matches[2]),
        ];
    }

    private function normalizeSideCode(string $value): ?string
    {
        $value = mb_strtoupper(trim($value));
        $value = strtr($value, [
            'А' => 'A',
            'В' => 'B',
            'С' => 'C',
        ]);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return $value === '' ? null : $value;
    }

    private function emptyToNull(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' || $value === '00000000-0000-0000-0000-000000000000' ? null : $value;
    }
}
