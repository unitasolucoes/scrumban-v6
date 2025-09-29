/**
 * SCRUMBAN PLUGIN - JAVASCRIPT PRINCIPAL
 * Funcionalidades para quadro Kanban, drag & drop, modais e interações
 */

// Configurações globais
var ScrumbanConfig = {
    baseUrl: '/plugins/scrumban',
    ajaxUrl: '/plugins/scrumban/ajax',
    currentBoardId: null,
    currentTeamId: null,
    canEdit: false,
    canManage: false
};

// Inicialização quando o DOM estiver pronto
$(document).ready(function() {
    initializeScrumban();
});

/**
 * Inicializar todas as funcionalidades do Scrumban
 */
function initializeScrumban() {
    setupCSRF();
    initializeBootstrapComponents();
    
    if (ScrumbanConfig.canEdit) {
        initializeDragDrop();
    }
    
    setupFilters();
    setupModals();
    setupForms();
    setupNotifications();
}

/**
 * Configurar token CSRF para requests AJAX
 */
function setupCSRF() {
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                xhr.setRequestHeader("X-CSRFToken", $('meta[name=csrf-token]').attr('content'));
            }
        }
    });
}

/**
 * Inicializar componentes do Bootstrap
 */
function initializeBootstrapComponents() {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();
    $('.alert-dismissible').alert();
}

/**
 * Configurar drag & drop para o quadro Kanban
 */
function initializeDragDrop() {
    if (!$('.kanban-cards').length) return;
    
    $('.kanban-cards').sortable({
        connectWith: '.kanban-cards',
        placeholder: 'card-placeholder',
        tolerance: 'pointer',
        cursor: 'move',
        distance: 5,
        opacity: 0.8,
        helper: function(event, element) {
            return element.clone().addClass('dragging');
        },
        start: function(event, ui) {
            ui.item.addClass('dragging');
            $('.card-placeholder').height(ui.item.outerHeight());
        },
        stop: function(event, ui) {
            ui.item.removeClass('dragging');
        },
        update: function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var cardId = ui.item.data('card-id');
                var newStatus = ui.item.closest('.kanban-column').data('status');
                var position = ui.item.index();
                
                updateCardStatus(cardId, newStatus, position);
            }
        }
    }).disableSelection();
}

/**
 * Atualizar status do card via AJAX
 */
function updateCardStatus(cardId, newStatus, position) {
    if (!cardId || !newStatus) return;
    
    showLoading();
    
    $.ajax({
        url: ScrumbanConfig.ajaxUrl + '/card.php',
        type: 'POST',
        data: {
            action: 'update_status',
            card_id: cardId,
            status: newStatus,
            position: position || 0
        },
        success: function(response) {
            hideLoading();
            
            try {
                var result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    showNotification('Status atualizado com sucesso', 'success');
                    updateColumnCounts();
                    
                    if (newStatus === 'done') {
                        updateCardCompletionDate(cardId);
                    }
                } else {
                    showNotification(result.error || 'Erro ao atualizar status', 'error');
                    location.reload();
                }
            } catch (e) {
                showNotification('Erro ao processar resposta', 'error');
                location.reload();
            }
        },
        error: function() {
            hideLoading();
            showNotification('Erro de conexão', 'error');
            location.reload();
        }
    });
}

/**
 * Atualizar data de conclusão do card
 */
function updateCardCompletionDate(cardId) {
    var now = new Date().toLocaleString('pt-BR');
    var card = $('.kanban-card[data-card-id="' + cardId + '"]');
    card.find('.completion-date').text('Concluído em: ' + now);
}

/**
 * Mostrar modal com detalhes do card
 */
function showCardModal(cardId) {
    if (!cardId) return;
    
    showLoading();
    
    $.ajax({
        url: ScrumbanConfig.ajaxUrl + '/card.php',
        type: 'POST',
        data: {
            action: 'get_card_details',
            card_id: cardId
        },
        success: function(response) {
            hideLoading();
            $('#cardModalBody').html(response);
            $('#cardModal').modal('show');
            setupCardModal();
        },
        error: function() {
            hideLoading();
            showNotification('Erro ao carregar detalhes do card', 'error');
        }
    });
}

/**
 * Configurar funcionalidades do modal do card
 */
function setupCardModal() {
    // Form de comentário
    $('#addCommentForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        var cardId = $(this).find('input[name="card_id"]').val();
        var comment = $(this).find('textarea[name="comment"]').val().trim();
        
        if (comment) {
            addComment(cardId, comment);
        } else {
            showNotification('Digite um comentário', 'warning');
        }
    });
    
    // Botões de ação rápida
    $('.quick-action-btn').off('click').on('click', function() {
        var action = $(this).data('action');
        var cardId = $(this).data('card-id');
        executeQuickAction(action, cardId);
    });
    
    setupInlineEditing();
}

/**
 * Adicionar comentário ao card
 */
function addComment(cardId, comment) {
    $.ajax({
        url: ScrumbanConfig.ajaxUrl + '/card.php',
        type: 'POST',
        data: {
            action: 'add_comment',
            card_id: cardId,
            comment: comment
        },
        success: function(response) {
            try {
                var result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    showNotification('Comentário adicionado', 'success');
                    showCardModal(cardId);
                } else {
                    showNotification(result.error || 'Erro ao adicionar comentário', 'error');
                }
            } catch (e) {
                showNotification('Erro ao processar resposta', 'error');
            }
        },
        error: function() {
            showNotification('Erro de conexão', 'error');
        }
    });
}

/**
 * Executar ações rápidas no card
 */
function executeQuickAction(action, cardId) {
    switch (action) {
        case 'assign_to_me':
            assignCardToUser(cardId, getCurrentUserId());
            break;
        case 'move_to_todo':
            updateCardStatus(cardId, 'todo');
            break;
        case 'move_to_progress':
            updateCardStatus(cardId, 'em-execucao');
            break;
        case 'move_to_done':
            updateCardStatus(cardId, 'done');
            break;
        default:
            showNotification('Ação não implementada', 'warning');
    }
}

/**
 * Configurar filtros do quadro
 */
function setupFilters() {
    $('#assignee_filter, #type_filter, #priority_filter, #sprint_filter, #team_filter').on('change', function() {
        applyFilters();
    });
    
    var textFilterTimeout;
    $('#text_filter').on('input', function() {
        clearTimeout(textFilterTimeout);
        textFilterTimeout = setTimeout(function() {
            applyFilters();
        }, 300);
    });
    
    $('#toggle_filters').on('click', function() {
        toggleAdvancedFilters();
    });
    
    $('#clear_filters').on('click', function() {
        clearAllFilters();
    });
}

/**
 * Aplicar filtros aos cards
 */
function applyFilters() {
    var filters = {
        assignee: $('#assignee_filter').val(),
        type: $('#type_filter').val(),
        priority: $('#priority_filter').val(),
        sprint: $('#sprint_filter').val(),
        team: $('#team_filter').val(),
        text: $('#text_filter').val().toLowerCase()
    };
    
    var visibleCount = 0;
    
    $('.kanban-card').each(function() {
        var card = $(this);
        var show = true;
        
        Object.keys(filters).forEach(function(filterKey) {
            if (filters[filterKey] && show) {
                var cardValue = card.data(filterKey);
                
                if (filterKey === 'text') {
                    var cardText = card.text().toLowerCase();
                    show = cardText.includes(filters[filterKey]);
                } else {
                    show = cardValue == filters[filterKey];
                }
            }
        });
        
        card.toggle(show);
        if (show) visibleCount++;
    });
    
    updateColumnCounts();
    updateFilterCounter(visibleCount);
}

/**
 * Atualizar contadores das colunas
 */
function updateColumnCounts() {
    $('.kanban-column').each(function() {
        var column = $(this);
        var visible = column.find('.kanban-card:visible').length;
        var total = column.find('.kanban-card').length;
        
        var badge = column.find('.badge');
        if (badge.length) {
            badge.text(visible);
        }
    });
}

/**
 * Atualizar contador de filtros
 */
function updateFilterCounter(count) {
    if ($('#filter_counter').length) {
        $('#filter_counter').text(count + ' cards visíveis');
    }
}

/**
 * Toggle filtros avançados
 */
function toggleAdvancedFilters() {
    $('#advanced_filters').slideToggle();
    $('#toggle_filters i').toggleClass('fa-chevron-down fa-chevron-up');
}

/**
 * Limpar todos os filtros
 */
function clearAllFilters() {
    $('#assignee_filter, #type_filter, #priority_filter, #sprint_filter, #team_filter').val('');
    $('#text_filter').val('');
    applyFilters();
}

/**
 * Configurar modais
 */
function setupModals() {
    $('#newCardModal').on('shown.bs.modal', function() {
        $(this).find('input[name="name"]').focus();
    });
    
    $('#teamModal, #newTeamModal').on('shown.bs.modal', function() {
        $(this).find('input[name="name"]').focus();
    });
    
    $('.modal').on('hidden.bs.modal', function() {
        var form = $(this).find('form')[0];
        if (form) form.reset();
        $(this).find('.alert').remove();
    });
}

/**
 * Configurar formulários
 */
function setupForms() {
    $('#newCardForm').on('submit', function(e) {
        e.preventDefault();
        submitNewCard();
    });
    
    $('#newTeamForm').on('submit', function(e) {
        e.preventDefault();
        submitNewTeam();
    });
    
    $('#addMemberForm').on('submit', function(e) {
        e.preventDefault();
        addTeamMember();
    });
    
    $('#addBoardForm').on('submit', function(e) {
        e.preventDefault();
        addTeamBoard();
    });
}

/**
 * Submeter formulário de novo card
 */
function submitNewCard() {
    var formData = $('#newCardForm').serialize();
    formData += '&action=create';
    
    if (ScrumbanConfig.currentBoardId) {
        formData += '&boards_id=' + ScrumbanConfig.currentBoardId;
    }
    
    $.ajax({
        url: ScrumbanConfig.ajaxUrl + '/card.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            try {
                var result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    $('#newCardModal').modal('hide');
                    showNotification('Card criado com sucesso', 'success');
                    location.reload();
                } else {
                    showFormError('#newCardForm', result.error || 'Erro ao criar card');
                }
            } catch (e) {
                showFormError('#newCardForm', 'Erro ao processar resposta');
            }
        },
        error: function() {
            showFormError('#newCardForm', 'Erro de conexão');
        }
    });
}

/**
 * Configurar edição inline
 */
function setupInlineEditing() {
    $('.editable-field').off('click').on('click', function() {
        var field = $(this);
        var value = field.text();
        var input = $('<input type="text" class="form-control form-control-sm">').val(value);
        
        field.empty().append(input);
        input.focus().select();
        
        input.on('blur keypress', function(e) {
            if (e.type === 'blur' || e.which === 13) {
                var newValue = $(this).val();
                updateCardField(field.data('card-id'), field.data('field'), newValue);
                field.text(newValue);
            }
        });
    });
}

/**
 * Atualizar campo do card
 */
function updateCardField(cardId, field, value) {
    $.ajax({
        url: ScrumbanConfig.ajaxUrl + '/card.php',
        type: 'POST',
        data: {
            action: 'update_field',
            card_id: cardId,
            field: field,
            value: value
        },
        success: function(response) {
            try {
                var result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    showNotification('Campo atualizado', 'success');
                } else {
                    showNotification(result.error || 'Erro ao atualizar', 'error');
                }
            } catch (e) {
                showNotification('Erro ao processar resposta', 'error');
            }
        }
    });
}

/**
 * Configurar sistema de notificações
 */
function setupNotifications() {
    if (!$('#notification-container').length) {
        $('body').append('<div id="notification-container" class="toast-container"></div>');
    }
}

/**
 * Mostrar notificação
 */
function showNotification(message, type, duration) {
    type = type || 'info';
    duration = duration || 3000;
    
    var icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    var colors = {
        success: 'text-success',
        error: 'text-danger',
        warning: 'text-warning',
        info: 'text-info'
    };
    
    var toastId = 'toast-' + Date.now();
    var toast = $('<div id="' + toastId + '" class="toast" role="alert">' +
        '<div class="toast-header">' +
            '<i class="fas ' + icons[type] + ' ' + colors[type] + ' mr-2"></i>' +
            '<strong class="mr-auto">' + type.charAt(0).toUpperCase() + type.slice(1) + '</strong>' +
            '<button type="button" class="ml-2 mb-1 close" data-dismiss="toast">' +
                '<span>&times;</span>' +
            '</button>' +
        '</div>' +
        '<div class="toast-body">' + message + '</div>' +
    '</div>');
    
    $('#notification-container').append(toast);
    toast.toast({ delay: duration }).toast('show');
    
    toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

/**
 * Mostrar erro em formulário
 */
function showFormError(formSelector, message) {
    var form = $(formSelector);
    var alert = $('<div class="alert alert-danger alert-dismissible fade show">' +
        message +
        '<button type="button" class="close" data-dismiss="alert">' +
            '<span>&times;</span>' +
        '</button>' +
    '</div>');
    
    form.find('.modal-body').prepend(alert);
}

/**
 * Mostrar indicador de carregamento
 */
function showLoading() {
    if (!$('#loading-overlay').length) {
        var overlay = $('<div id="loading-overlay" class="loading-overlay">' +
            '<div class="loading-spinner">' +
                '<i class="fas fa-spinner fa-spin fa-2x"></i>' +
            '</div>' +
        '</div>');
        $('body').append(overlay);
    }
    $('#loading-overlay').fadeIn();
}

/**
 * Esconder indicador de carregamento
 */
function hideLoading() {
    $('#loading-overlay').fadeOut();
}

/**
 * Obter ID do usuário atual
 */
function getCurrentUserId() {
    return $('meta[name="user-id"]').attr('content') || null;
}

/**
 * Atribuir card a um usuário
 */
function assignCardToUser(cardId, userId) {
    $.ajax({
        url: ScrumbanConfig.ajaxUrl + '/card.php',
        type: 'POST',
        data: {
            action: 'assign_card',
            card_id: cardId,
            user_id: userId
        },
        success: function(response) {
            try {
                var result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    showNotification('Card atribuído com sucesso', 'success');
                    updateCardAssignee(cardId, result.user_name);
                } else {
                    showNotification(result.error || 'Erro ao atribuir card', 'error');
                }
            } catch (e) {
                showNotification('Erro ao processar resposta', 'error');
            }
        }
    });
}

/**
 * Atualizar visualmente o responsável do card
 */
function updateCardAssignee(cardId, userName) {
    var card = $('.kanban-card[data-card-id="' + cardId + '"]');
    card.find('.card-assignee').text(userName);
    card.data('assignee', getCurrentUserId());
}

/**
 * Funções específicas para gerenciamento de equipes
 */
var TeamManagement = {
    
    /**
     * Mostrar modal para adicionar membro
     */
    showAddMemberModal: function(teamId) {
        $('#modal_team_id').val(teamId);
        $('#addMemberModal').modal('show');
    },
    
    /**
     * Adicionar membro à equipe
     */
    addMember: function() {
        var formData = $('#addMemberForm').serialize();
        
        $.ajax({
            url: ScrumbanConfig.ajaxUrl + '/team.php',
            type: 'POST',
            data: formData + '&action=add_member',
            success: function(response) {
                try {
                    var result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        $('#addMemberModal').modal('hide');
                        showNotification('Membro adicionado com sucesso', 'success');
                        location.reload();
                    } else {
                        showFormError('#addMemberForm', result.error || 'Erro ao adicionar membro');
                    }
                } catch (e) {
                    showFormError('#addMemberForm', 'Erro ao processar resposta');
                }
            }
        });
    },
    
    /**
     * Alterar papel do usuário
     */
    changeUserRole: function(memberId, newRole) {
        $.ajax({
            url: ScrumbanConfig.ajaxUrl + '/team.php',
            type: 'POST',
            data: {
                action: 'change_role',
                member_id: memberId,
                role: newRole
            },
            success: function(response) {
                try {
                    var result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        showNotification('Papel alterado com sucesso', 'success');
                    } else {
                        showNotification(result.error || 'Erro ao alterar papel', 'error');
                        location.reload();
                    }
                } catch (e) {
                    showNotification('Erro ao processar resposta', 'error');
                    location.reload();
                }
            }
        });
    },
    
    /**
     * Remover membro da equipe
     */
    removeMember: function(memberId, memberName) {
        if (confirm('Tem certeza que deseja remover ' + memberName + ' da equipe?')) {
            $.ajax({
                url: ScrumbanConfig.ajaxUrl + '/team.php',
                type: 'POST',
                data: {
                    action: 'remove_member',
                    member_id: memberId
                },
                success: function(response) {
                    try {
                        var result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            showNotification('Membro removido com sucesso', 'success');
                            location.reload();
                        } else {
                            showNotification(result.error || 'Erro ao remover membro', 'error');
                        }
                    } catch (e) {
                        showNotification('Erro ao processar resposta', 'error');
                    }
                }
            });
        }
    },
    
    /**
     * Mostrar modal para adicionar quadro
     */
    showAddBoardModal: function(teamId) {
        $('#modal_board_team_id').val(teamId);
        $('#addBoardModal').modal('show');
    },
    
    /**
     * Adicionar quadro à equipe
     */
    addBoard: function() {
        var formData = $('#addBoardForm').serialize();
        
        $.ajax({
            url: ScrumbanConfig.ajaxUrl + '/team.php',
            type: 'POST',
            data: formData + '&action=add_board',
            success: function(response) {
                try {
                    var result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        $('#addBoardModal').modal('hide');
                        showNotification('Quadro associado com sucesso', 'success');
                        location.reload();
                    } else {
                        showFormError('#addBoardForm', result.error || 'Erro ao associar quadro');
                    }
                } catch (e) {
                    showFormError('#addBoardForm', 'Erro ao processar resposta');
                }
            }
        });
    },
    
    /**
     * Editar permissões do quadro
     */
    editBoardPermissions: function(teamBoardId, canEdit, canManage) {
        $('#edit_team_board_id').val(teamBoardId);
        $('#edit_can_edit').prop('checked', canEdit);
        $('#edit_can_manage').prop('checked', canManage);
        $('#editPermissionsModal').modal('show');
    },
    
    /**
     * Salvar permissões editadas
     */
    savePermissions: function() {
        var formData = $('#editPermissionsForm').serialize();
        
        $.ajax({
            url: ScrumbanConfig.ajaxUrl + '/team.php',
            type: 'POST',
            data: formData + '&action=update_permissions',
            success: function(response) {
                try {
                    var result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        $('#editPermissionsModal').modal('hide');
                        showNotification('Permissões atualizadas', 'success');
                        location.reload();
                    } else {
                        showFormError('#editPermissionsForm', result.error || 'Erro ao atualizar permissões');
                    }
                } catch (e) {
                    showFormError('#editPermissionsForm', 'Erro ao processar resposta');
                }
            }
        });
    },
    
    /**
     * Remover quadro da equipe
     */
    removeBoardFromTeam: function(teamBoardId, boardName) {
        if (confirm('Tem certeza que deseja remover o quadro "' + boardName + '" da equipe?')) {
            $.ajax({
                url: ScrumbanConfig.ajaxUrl + '/team.php',
                type: 'POST',
                data: {
                    action: 'remove_board',
                    team_board_id: teamBoardId
                },
                success: function(response) {
                    try {
                        var result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            showNotification('Quadro removido da equipe', 'success');
                            location.reload();
                        } else {
                            showNotification(result.error || 'Erro ao remover quadro', 'error');
                        }
                    } catch (e) {
                        showNotification('Erro ao processar resposta', 'error');
                    }
                }
            });
        }
    }
};

/**
 * Funções específicas para dashboard
 */
var Dashboard = {
    
    /**
     * Atualizar seletor de quadros baseado na equipe
     */
    updateBoardSelector: function(teamId) {
        $.ajax({
            url: ScrumbanConfig.ajaxUrl + '/dashboard.php',
            type: 'POST',
            data: {
                action: 'get_team_boards',
                team_id: teamId
            },
            success: function(response) {
                $('#board_selector').html(response);
            }
        });
    },
    
    /**
     * Navegar para quadro selecionado
     */
    navigateToBoard: function(boardId) {
        if (boardId) {
            window.location.href = ScrumbanConfig.baseUrl + '/front/board.php?id=' + boardId;
        }
    }
};

/**
 * Funções para gerenciamento de sprints
 */
var SprintManagement = {
    
    /**
     * Ativar sprint
     */
    activateSprint: function(sprintId) {
        if (confirm('Ativar este sprint? Isso desativará outros sprints do mesmo quadro.')) {
            $.ajax({
                url: ScrumbanConfig.ajaxUrl + '/sprint.php',
                type: 'POST',
                data: {
                    action: 'activate',
                    sprint_id: sprintId
                },
                success: function(response) {
                    try {
                        var result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            showNotification('Sprint ativado com sucesso', 'success');
                            location.reload();
                        } else {
                            showNotification(result.error || 'Erro ao ativar sprint', 'error');
                        }
                    } catch (e) {
                        showNotification('Erro ao processar resposta', 'error');
                    }
                }
            });
        }
    },
    
    /**
     * Desativar sprint
     */
    deactivateSprint: function(sprintId) {
        if (confirm('Desativar este sprint?')) {
            $.ajax({
                url: ScrumbanConfig.ajaxUrl + '/sprint.php',
                type: 'POST',
                data: {
                    action: 'deactivate',
                    sprint_id: sprintId
                },
                success: function(response) {
                    try {
                        var result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            showNotification('Sprint desativado com sucesso', 'success');
                            location.reload();
                        } else {
                            showNotification(result.error || 'Erro ao desativar sprint', 'error');
                        }
                    } catch (e) {
                        showNotification('Erro ao processar resposta', 'error');
                    }
                }
            });
        }
    }
};

/**
 * Funções utilitárias
 */
var Utils = {
    
    /**
     * Formatar data para exibição
     */
    formatDate: function(dateString) {
        if (!dateString) return '';
        
        var date = new Date(dateString);
        return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    /**
     * Escapar HTML
     */
    escapeHtml: function(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    },
    
    /**
     * Debounce para otimizar performance
     */
    debounce: function(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
};

/**
 * Exportar funções para uso global
 */
window.ScrumbanConfig = ScrumbanConfig;
window.showCardModal = showCardModal;
window.updateCardStatus = updateCardStatus;
window.showNotification = showNotification;
window.TeamManagement = TeamManagement;
window.Dashboard = Dashboard;
window.SprintManagement = SprintManagement;
window.Utils = Utils;

// Aliases para compatibilidade com código existente
window.showAddMemberModal = TeamManagement.showAddMemberModal;
window.changeUserRole = TeamManagement.changeUserRole;
window.removeMember = TeamManagement.removeMember;
window.showAddBoardModal = TeamManagement.showAddBoardModal;
window.editBoardPermissions = TeamManagement.editBoardPermissions;
window.removeBoardFromTeam = TeamManagement.removeBoardFromTeam;
window.updateBoardSelector = Dashboard.updateBoardSelector;
window.activateSprint = SprintManagement.activateSprint;
window.deactivateSprint = SprintManagement.deactivateSprint;

/**
 * Funções específicas para modais
 */
function showNewCardModal() {
    $('#newCardModal').modal('show');
}

function showNewTeamModal() {
    $('#newTeamModal').modal('show');
}

function showNewSprintModal() {
    $('#newSprintModal').modal('show');
}

function showNewBoardModal() {
    $('#newBoardModal').modal('show');
}

// Exportar funções de modal
window.showNewCardModal = showNewCardModal;
window.showNewTeamModal = showNewTeamModal;
window.showNewSprintModal = showNewSprintModal;
window.showNewBoardModal = showNewBoardModal;