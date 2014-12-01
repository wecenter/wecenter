$(document).ready(function ()
{
    $('.aw-publish-btn .dropdown-list ul').append('<li><a href="' + G_BASE_URL + '/ticket/publish/">' + _t('工单') + '</a></li>');

    if (G_REQUEST_URL == '/publish/' || G_REQUEST_URL == '/publish/article/')
    {
        $('.aw-main-content ul.aw-nav-tabs').prepend('<li><a href="' + G_BASE_URL + '/ticket/publish/">' + _t('工单') + '</a></li>');
    }
})
