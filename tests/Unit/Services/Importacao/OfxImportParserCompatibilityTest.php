<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Services\Importacao\ImportPreviewService;
use Application\Services\Importacao\Parsers\OfxImportParser;
use PHPUnit\Framework\TestCase;

class OfxImportParserCompatibilityTest extends TestCase
{
    public function testParsesCp1252TextWhenCharsetIsDeclared(): void
    {
        $parser = new OfxImportParser();
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 1, 'source_type' => 'ofx']);

        $name1252 = 'Cr' . chr(233) . 'dito Loja';
        $memo1252 = 'Pagamento cart' . chr(227) . 'o';
        $contents = implode("\n", [
            'OFXHEADER:100',
            'CHARSET:1252',
            '<OFX>',
            '<STMTTRN>',
            '<TRNAMT>-10.50',
            '<DTPOSTED>20260401',
            "<NAME>{$name1252}",
            "<MEMO>{$memo1252}",
            '</STMTTRN>',
            '</OFX>',
        ]);

        $rows = $parser->parse($contents, $profile);

        $this->assertCount(1, $rows);
        $this->assertSame($this->fromWindows1252($name1252), $rows[0]->description);
        $this->assertSame($this->fromWindows1252($memo1252), $rows[0]->memo);
    }

    public function testParsesAmountsWithMixedThousandSeparators(): void
    {
        $parser = new OfxImportParser();
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 2, 'source_type' => 'ofx']);

        $contents = '<OFX>'
            . '<STMTTRN><TRNAMT>1.234,56</TRNAMT><DTPOSTED>20260401</DTPOSTED><NAME>Entrada</NAME></STMTTRN>'
            . '<STMTTRN><TRNAMT>-1,234.56</TRNAMT><DTPOSTED>20260402</DTPOSTED><NAME>Saida</NAME></STMTTRN>'
            . '</OFX>';

        $rows = $parser->parse($contents, $profile);

        $this->assertCount(2, $rows);
        $this->assertSame(1234.56, $rows[0]->amount);
        $this->assertSame('receita', $rows[0]->type);
        $this->assertSame(1234.56, $rows[1]->amount);
        $this->assertSame('despesa', $rows[1]->type);
    }

    public function testPreviewWarnsWhenProfileAndOfxAccountMetadataDiffer(): void
    {
        $service = new ImportPreviewService([new OfxImportParser()]);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 3,
            'source_type' => 'ofx',
            'agencia' => '9999',
            'numero_conta' => '00000-0',
        ]);

        $preview = $service->preview('ofx', $this->sampleOfxWithBankAccount(), $profile, 'extrato.ofx', 'conta');

        $warnings = is_array($preview['warnings'] ?? null) ? $preview['warnings'] : [];
        $warningsText = strtolower(implode(' ', $warnings));

        $this->assertTrue((bool) ($preview['can_confirm'] ?? false));
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('branchid', $warningsText);
        $this->assertStringContainsString('acctid', $warningsText);
    }

    public function testPreviewDoesNotWarnWhenProfileMatchesOfxAccountMetadata(): void
    {
        $service = new ImportPreviewService([new OfxImportParser()]);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 4,
            'source_type' => 'ofx',
            'agencia' => '1713-2',
            'numero_conta' => '42555-9',
        ]);

        $preview = $service->preview('ofx', $this->sampleOfxWithBankAccount(), $profile, 'extrato.ofx', 'conta');
        $warnings = is_array($preview['warnings'] ?? null) ? $preview['warnings'] : [];

        $this->assertTrue((bool) ($preview['can_confirm'] ?? false));
        $this->assertSame([], $warnings);
    }

    private function fromWindows1252(string $value): string
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        if (function_exists('iconv')) {
            $converted = iconv('Windows-1252', 'UTF-8//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return $value;
    }

    private function sampleOfxWithBankAccount(): string
    {
        return <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252

<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKACCTFROM>
          <BANKID>1
          <BRANCHID>1713-2
          <ACCTID>42555-9
          <ACCTTYPE>CHECKING
        </BANKACCTFROM>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT
            <DTPOSTED>20260402
            <TRNAMT>-100.00
            <FITID>OFX-ACC-1
            <NAME>Teste
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
OFX;
    }
}
