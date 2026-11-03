# Talo Aulas

Agenda de aulas para estúdio de pilates, em Laravel 13 e Livewire 4: créditos com validade, fila de espera que chama a próxima aluna e Pix que confirma sozinho.

**Projeto conceito.** O Talo Pilates, de Ilhéus, é fictício, e as alunas da demonstração são inventadas.

- Demonstração (o próprio Laravel rodando no navegador): https://luisantoniodev.tech/projetos/talo-aulas/
- Portfólio: https://luisantoniodev.tech

## O problema

Num estúdio de pilates, a agenda é uma planilha e um WhatsApp que não para:

- **Vagas contadas.** As turmas de aparelhos têm quatro lugares, porque são quatro aparelhos.
- **Fila no braço.** Toda desistência vira uma rodada de mensagens até alguém da lista de espera responder. Quando ninguém responde, o aparelho fica vazio.
- **Saldo de cabeça.** Ninguém sabe quantas aulas cada aluna ainda tem nem quando elas vencem.

## O que o sistema faz

São três telas:

- **Aluna, pelo celular:** vê a próxima aula, reserva, desmarca e entra na fila.
- **Recepção, no computador:** acompanha o dia e a semana, encaixa alunas, cancela aulas e confere os Pix.
- **Instrutora, no tablet:** marca presença e lê as observações de saúde.

Por trás:

- **Aulas em lotes com validade.** Cada mês pago, reposição ou cortesia vira um lote, e a reserva gasta o que vence primeiro. O saldo nunca é editado à mão: cada entrada e saída é uma linha no extrato.
- **Prazos.** A agenda abre uma semana antes. A aluna reserva até meia hora antes; depois disso, só a recepção encaixa. Cancelando até 6 horas antes, a aula volta. Depois disso não volta, mas o plano com reposição dá uma aula extra de 30 dias, sem a aluna pedir.
- **Fila de espera em três velocidades.**
  - Com folga, quem pediu reserva automática ganha a vaga na hora.
  - Quem pediu aviso recebe uma oferta de 30 minutos, e o prazo vencido passa a vez para a próxima.
  - A menos de 1h30 da aula, a vaga vai para toda a fila ao mesmo tempo e fica com quem confirmar primeiro.
- **Pix.** A cobrança do ciclo seguinte sai cinco dias antes. O aviso do gateway é conferido pela assinatura e gravado com chave única, porque o gateway reenvia o mesmo aviso. Pago duas vezes vira um ciclo só. Pago com valor diferente fica segurado para a recepção conferir.
- **Rotinas.** Lembretes, faltas, vencimentos e ofertas expiradas rodam no agendador do Laravel.

As regras do estúdio ficam em `config/talo.php`, num lugar só, porque são combinadas com a dona e mudam com o tempo.

## Por que Laravel

O problema é de regras, não de telas: créditos com validade, prazos, fila, cobrança e avisos agendados. O Laravel já traz fila de tarefas, agendador, notificações, migrações e testes com banco de verdade.

- **Hospedagem e manutenção acessíveis.** PHP roda em qualquer hospedagem do Brasil, e há muita gente que programa em Laravel: o estúdio não fica preso a quem fez o sistema.
- **Uma base só.** Com Livewire, as telas reagem na hora sem uma API e um front-end separados para manter em sincronia.

## A última vaga, disputada no mesmo segundo

A reserva trava a linha da aula no banco (`lockForUpdate`) antes de ler quantas vagas sobram. O teste em `tests/Corrida` dispara oito processos PHP de verdade contra uma aula de uma vaga, no MySQL, todos no mesmo instante. Entra uma aluna e sete recebem "lotada".

Para conferir que a trava é o que faz isso, rodei o mesmo teste sem ela. As oito transações se atropelam e o MySQL derruba parte delas por deadlock: a aluna veria um erro em vez de "lotada".

## Rodar

Com PHP 8.4 e Composer:

```bash
composer install
cp .env.example .env
php artisan key:generate
vendor/bin/pest
vendor/bin/phpstan analyse
```

A corrida só roda no MySQL; no SQLite ela é pulada. Para rodar tudo no MySQL, com Docker:

```bash
docker network create talo
docker run -d --name talo-mysql --network talo -e MYSQL_DATABASE=talo -e MYSQL_USER=talo -e MYSQL_PASSWORD=talo -e MYSQL_ROOT_PASSWORD=raiz mysql:8.4
docker build --target teste -t talo-aulas:teste .
docker run --rm --network talo -e DB_CONNECTION=mysql -e DB_HOST=talo-mysql -e DB_DATABASE=talo -e DB_USERNAME=talo -e DB_PASSWORD=talo talo-aulas:teste
```

Em produção, a imagem `--target producao` roda o FrankenPHP como usuário sem privilégio e responde ao healthcheck em `/up`.

## Testes

- 19 testes de regras no SQLite: reserva, cancelamento e reposição, fila nas três velocidades, créditos que vencem, fechamento da aula, geração da agenda com feriado e o webhook do Pix.
- A corrida dos oito processos, no MySQL.
- Larastan no nível 6.
- A imagem Docker construída e testada no CI.

### Sobre este repositório

O código da aplicação veio do pacote da demonstração do portfólio (o mesmo Laravel que roda no navegador). Os testes, o Dockerfile e o CI foram refeitos, porque a primeira versão do repositório se perdeu com o ambiente onde foi escrita.
