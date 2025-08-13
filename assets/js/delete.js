// esse script faz a função de deletar os arquivos com confirmação
function confirmarExclusao(id, nomeArquivo) {
    if (confirm('Tem certeza que deseja excluir o arquivo "' + nomeArquivo + '"?')) {
        // Chamada AJAX para excluir o arquivo
        fetch('excluir_arquivo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Arquivo excluído com sucesso!');
                    location.reload(); // Recarrega a página para atualizar a lista
                } else {
                    alert('Erro ao excluir arquivo: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao processar requisição');
            });
    }
}