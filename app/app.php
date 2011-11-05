<?php
require_once(dirname(__FILE__).'/../conf.php');
$vsimg = apath('vs.png');
?>
<script type="text/javascript" src="<?php echo apath('js/jquery-1.6.4.min.js') ?>"></script>
<script type="text/javascript" src="<?php echo apath('js/jquery-ui-1.8.16.custom.min.js') ?>"></script>
<script type="text/javascript" src="<?php echo apath('js/opensocial-jquery.min.js') ?>"></script>
<script type="text/javascript" src="<?php echo apath('js/jquery.pagination.js') ?>"></script>
<style type="text/css">
<!--
  .screen {
    display: none;
  }
  #search-result ul {
    list-style: none;
    margin: 0;
    padding: 0;
    width: 570px;
  }

  #search-result ul li {
    padding: 0 0 0 10px;
    float: left;
    width: 100px;
    height: 100px;
    margin: 0;
  }

  #search-result ul li a {
    display: block;
    width: 95px;
    height: 95px;
    overflow: hidden;
  }

  #search-result ul li a:hover {
    border: solid 1px #CCCCCC;
  }

  #search-result ul li .name {
    font-size: 0.7em;
  }

  #form-question {
    clear: both;
  }

  .vs ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .vs ul li {
    padding: 0px;
    float: left;
    width: 130px;
    margin: 0;
  }

  .vs ul li.vs-text {
    height: 150px;
    width: 150px;
    text-align: center;
    font-weight: bold;
    background-image: url("<?php echo $vsimg ?>");
    background-repeat: no-repeat;
  }

  .vs ul li.vs-text div {
    color: white;
    font-size: 1.2em;
    margin-top: 65px;
  }


  .vs ul li .item-name {
    font-size: 0.9em;
  }

  .vs ul li .price {
    color: #CC0000;
    font-size: 1.2em;
  }

  .vs ul li .votem ul li {
    float: left;
    width: 40px;
    margin: 0;
  }


  #form-question-input {
    display: none;
    clear: both;
  }

  #search-pager {
    clear: both;
  }

  .pagination {
    font-size: 80%;
  }

  .pagination a {
     text-decoration: none;
     border: solid 1px #AAE;
     color: #15B;
  }

  .pagination a, .pagination span {
    display: block;
    float: left;
    padding: 0.3em 0.5em;
    margin-right: 5px;
    margin-bottom: 5px;
    min-width:1em;
    text-align:center;
  }

  .pagination .current {
    background: #26B;
    color: #fff;
	  border: solid 1px #AAE;
  }

  .pagination .current.prev, .pagination .current.next{
    color:#999;
    border-color:#999;
    background:#fff;
  }
-->
</style>
<script type="text/javascript">
jQuery(document).ready(function($, data) {
  var path = '<?php echo apath() ?>';
  var rakutenDeveloperId = '<?php echo DEVELOPER_ID ?>';

  var objectSize = function(obj) {
    var l=0;
    $.each(obj, function(i, elem) { l++; });
    return l;
  }

  var numberFormat = function(num){
      return num.toString().replace(/([\d]+?)(?=(?:\d{3})+$)/g, function(t){ return t + ','; });
  }

  var RakutenIchiba = function() {
    this.selected = {};
  };
  RakutenIchiba.prototype = {
    _getUrl: function() { return "http://api.rakuten.co.jp/rws/3.0/json"; },
    _getGenreParameter: function(genre) {
      return {operation: "GenreSearch", version: "2007-04-11", developerId: rakutenDeveloperId, genreId: genre};
    },
    _getSearchParameter: function(skeyword, pagen) {
      var genre = 0;
      var genre3 = $('#genre3 select');
      if (genre3) {
        genre = genre3.val() || 0;
      }
      if (0 == genre) {
        var genre2 = $('#genre2 select');
        if (genre2) {
          genre = genre2.val() || 0;
        }
      }
      if (0 == genre) {
        var genre1 = $('#genre1 select');
        if (genre1) {
          genre = genre1.val() || 0;
        }
      }

      return {
        operation: "ItemSearch",
        version: "2010-09-15",
        developerId: rakutenDeveloperId,
        keyword: skeyword,
        genreId: genre,
        hits: 20,
        page: pagen
      };
    },
    _getSearchByItemCodeParameter: function(code) {
      return {operation: "ItemCodeSearch", version: "2010-08-05", developerId: rakutenDeveloperId, itemCode: code};
    },

    _build: function(obj, data, blankLabel) {
      if (data.Body && data.Body.GenreSearch && data.Body.GenreSearch.child instanceof Array) {
        var genre = data.Body.GenreSearch.child;
        var select = $('<select>');
        select.append($('<option>').attr('value', '').text(blankLabel));
        for (var i = 0;i < genre.length;i++) {
          select.append($('<option>').attr('value', genre[i].genreId).text(genre[i].genreName));
        }
        obj.append(select);

        $(window).adjustHeight();
        return select;
      }

      return false;
    },

    searchByItemCode: function(callback, code) {
      var t = this;
      $.ajax({
        url: t._getUrl(),
        data: t._getSearchByItemCodeParameter(code),
        dataType: 'json',
        success: function(data) {
          if (data.Body && data.Body.ItemCodeSearch &&
            data.Body.ItemCodeSearch.Items &&
            data.Body.ItemCodeSearch.Items.Item instanceof Array &&
            data.Body.ItemCodeSearch.Items.Item.length >= 1
          ) {
            callback(data.Body.ItemCodeSearch.Items.Item[0]);
          }
      }});
    },

    getTopGenre: function() {
      var t = this;
      $.ajax({
        url: t._getUrl(),
        data: t._getGenreParameter(0),
        dataType: 'json',
        success: function(data) {
          if (select = t._build($("#genre1"), data, "大ジャンル")) {
            select.change(function(event){ return t.getSecondGenre.apply(t, [event])});
          }
      }});
    },

    getSecondGenre: function(event) {
      var t = this;
      var value = event.target.value;
      $("#genre2").empty();
      $("#genre3").empty();

      if (!value) return;

      $.ajax({
        url: t._getUrl(),
        data: t._getGenreParameter(value),
        dataType: 'json',
        success: function(data) {
          if (select = t._build($("#genre2"), data, "中ジャンル")) {
            select.change(function(event){ return t.getThirdGenre.apply(t, [event])});
          }
      }});
    },

    getThirdGenre: function(event) {
      var t = this;
      var value = event.target.value;
      $("#genre3").empty();

      if (!value) return;

      $.ajax({
        url: t._getUrl(),
        data: t._getGenreParameter(value),
        dataType: 'json',
        success: function(data) {
          t._build($("#genre3"), data, "小ジャンル");
      }});
    },

    _search: function(keyword, page, isGeneratePager) {
      var t = this;

      if (!keyword) return false;

      $.ajax({
        url: t._getUrl(),
        data: t._getSearchParameter(keyword, page),
        dataType: 'json',
        success: function(data) {
          if (data.Body && data.Body.ItemSearch && data.Body.ItemSearch.Items && data.Body.ItemSearch.Items.Item instanceof Array) {
            var items = data.Body.ItemSearch.Items.Item;
            var ul = $('<ul>');
            for (var i = 0; i < items.length; i++) {
              var item = items[i];
              var li = $('<li>');
              var a = $('<a>');

              var img = $('<img>');
              if (item.smallImageUrl) {
                img.attr('src', item.smallImageUrl);
              } else {
                img.attr('src', path + '/noimage.png');
              }
              img.attr('alt', item.itemName);
              a.append(img);
              a.append($('<div>').addClass('name').text(item.itemName));
              li.append(a);
              ul.append(li);

              a.bind('click', i, function(event) {
                item = items[event.data];
                var elements = $('#search-result ul li');

                $('#form-question').show();
                ul = $('#form-question-vs ul');

                if (ul.length == 0) {
                  ul = $('<ul>');
                  $('#form-question-vs').append(ul);
                }

                if (t.selected[item.itemCode]) {

                  return false;
                }

                if (objectSize(t.selected) < 2) {
                  li = $('<li>');
                  img = $('<img>');
                  if (item.mediumImageUrl) {
                    img.attr('src', item.mediumImageUrl);
                  } else {
                    img.attr('src', path + '/noimage.png');
                  }
                  img.bind('load', function() {
                    $(window).adjustHeight();
                  });
                  li.append(img);
                  li.append($('<div>').addClass('item-name').append($('<a>')
                    .attr('href', '#')
                    .click(function(event) { mixi.util.requestExternalNavigateTo(item.affiliateUrl || item.itemUrl); })
                    .text(item.itemName))
                  );
                  li.append($('<div>').addClass('price').text('￥' + numberFormat(item.itemPrice)));
                  ul.append(li);
                  t.selected[item.itemCode] = item;
                }

                if (objectSize(t.selected) == 1) {
                  ul.append($('<li>').addClass('vs-text').append($('<div>').text('VS')));
                }

                if (objectSize(t.selected) >= 2) {
                  $('#form-question-input').show();
                  $('#form-question').bind('submit', function(event) {
                    $.ajax({
                      url: '/appdata/@viewer/@self',
                      data: { fields: 'list' },
                      dataType: 'data',
                      success: function(data) {
                        if (!(data instanceof Object && data.list && data.list instanceof Object)) {
                          data = { list: {}};
                        }
                        var key = 'rakuten';
                        for (var k in t.selected) {
                          key += '_' + k;
                        }
                        data.list[key] = "";
                        $.ajax({
                          type: 'post',
                          url: '/appdata/@viewer/@self',
                          data: data,
                          dataType: 'data',
                          success: function() {
                          }
                        });
                      }
                    });

                    return false;
                  });
                }

                $(window).adjustHeight();
              });
            }
            $('#search-result').empty();
            $('#search-result').append(ul);
            if (isGeneratePager) {
              $('#search-pager').pagination(data.Body.ItemSearch.count, {
                items_per_page: 20,
                callback: function(page, jq) {
                  t._search.apply(t, [keyword, page + 1, false]);
                }
              });
            }
            $(window).adjustHeight();
          }
      }});

      return false;
    },

    search: function(event) {
      var input = $(event.target).children("input[name=keyword]");
      var value = input ? input.val() : undefined;
      return this._search(value, 1, true);
    },

    reset: function(event) {
      this.selected = {};
      $('#form-question-input').hide();
      var ul = $('#form-question-vs ul');
      if (ul.length != 0) {
        ul.empty();
      }
      $(window).adjustHeight();
    }
  };

  var topOperations = {
    'ichiba': new RakutenIchiba()
  };

  var fetchTopGenre = function(event) {
    var operation = $(this).val();
    var handle = topOperations[operation];
    $('#genre1').empty();
    $('#genre2').empty();
    $('#genre3').empty();

    if (undefined === handle) {
      return false;
    }

    handle.getTopGenre();
    $('#form-search').unbind();
    $('#form-search').bind('submit', function (event) { return handle.search.apply(handle, [event])});
    $('#form-question').bind('reset', function (event) { return handle.reset.apply(handle, [event])});
  };

  var buildScreenSearch = function() {
    $('#screen-search').show();
    $('#screen-vote').hide();
    fetchTopGenre.apply($('#genre0 select'));
    $(window).adjustHeight();
  }

  var buildScreenVote = function() {
    $('#screen-search').hide();
    $('#screen-vote').show();
    var codes = data.vote.split(",");
    topOperations.ichiba.searchByItemCode(function(item1) {
      topOperations.ichiba.searchByItemCode(function(item2) {
        var ul = $('<ul>');

        var addItem = function(item) {
          var li = $('<li>');
          var img = $('<img>');
          if (item.mediumImageUrl) {
            img.attr('src', item.mediumImageUrl);
          } else {
            img.attr('src', path + '/noimage.png');
          }
          img.bind('load', function() {
            $(window).adjustHeight();
          });
          li.append(img);
          li.append($('<div>').addClass('item-name').append($('<a>')
            .attr('href', '#')
            .click(function(event) { mixi.util.requestExternalNavigateTo(item.affiliateUrl || item.itemUrl); })
            .text(item.itemName))
          );
          li.append($('<div>').addClass('price').text('￥' + numberFormat(item.itemPrice)));

          var votediv = $('<div>').addClass('votem');
          var voteul = $('<ul>');
          votediv.append(voteul);
          for (var i = 0; i < 2; i++) {
            voteul.append($('<li>').append($('<img>').attr('src', 'http://img.mixi.jp/img/basic/common/noimage_member40.gif')));
          }
          li.append(votediv);

          ul.append(li);
        }

        addItem(item1);
        ul.append($('<li>').addClass('vs-text').append($('<div>').text('VS')));
        addItem(item2);
        $('#vote-vs').append(ul);

        $(window).adjustHeight();
      }, codes[1]);
    }, codes[0]);

    $.ajax({
      url: '/people/@owner/@self',
      data: {},
      dataType: 'data',
      success: function(people) {
        var person = people[0];
        var question1 = $('<div>').append(
          $('<img>')
          .attr('src', person.thumbnailUrl)
          .attr('alt', person.nickname)
        ).append(
          $('<a>')
          .attr('href', "#")
          .text(person.nickname)
          .click(function(event){
            $.view('profile');
            return false;
          })
        ).append('さんの質問');
        var question2 = $('<div>')
          .text("どちらがよさそうですか?");

        $('#vote-question').append(question1).append(question2);
      }
    });
  }

  if (data.vote) {
    buildScreenVote();
  } else {
    buildScreenSearch();
  }

  $('#genre0 select').change(fetchTopGenre);
  $('a [href=http://webservice.rakuten.co.jp/]').click(function(event) {
    mixi.util.requestExternalNavigateTo("mixi.util.requestExternalNavigateTo");
    return false;
  });
});
</script>
<!-- Rakuten Web Services Attribution Snippet FROM HERE -->
<a href="http://webservice.rakuten.co.jp/" target="_blank"><img src="http://webservice.rakuten.co.jp/img/credit/200709/credit_31130.gif" border="0" alt="楽天ウェブサービスセンター" title="楽天ウェブサービスセンター" width="311" height="30"/></a>
<!-- Rakuten Web Services Attribution Snippet TO HERE -->
<div id="screen-search" class="screen">
  <div id="form-search-content">
    <form id="form-search" action="">
      <div id="genre0">
        <select name="genre0">
          <option value="ichiba">楽天市場</option>
        </select>
      </div>
      <div id="genre1">
      </div>
      <div id="genre2">
      </div>
      <div id="genre3">
      </div>
      <input name="keyword" type="text" />
      <input type="submit" value="検索" />
    </form>
  </div>
  <div id="search-result"></div>
  <div id="search-pager"></div>
  <div id="form-question-content">
    <form id="form-question">
      <div id="form-question-vs" class="vs"></div>
      <div id="form-question-input">
        <label for="form-question-question">コメント: </label><input type="input" name="question" id="form-question-question" />
        <input type="submit" value="マイミクに聞く" />
        <input type="reset" value="リセット" />
      </div>
    </form>
  </div>
</div>
<div id="screen-vote" class="screen">
<div id="vote-question"></div>
<div id="vote-vs" class="vs"></div>
</div>
