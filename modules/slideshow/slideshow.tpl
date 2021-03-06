<?xml version="1.0" encoding="utf-8"?>
<tpl:templates
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:sql="http://2013.sylma.org/storage/sql"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
>

  <tpl:template mode="slideshow/contexts/css">

    <le:context name="css">
      <le:file>common.less</le:file>
    </le:context>

  </tpl:template>

  <tpl:template mode="slideshow/contexts">

    <js:include>/#sylma/ui/Loader.js</js:include>
    <js:include>/#sylma/device/Browser.js</js:include>
    <js:include>Container.js</js:include>
    <js:include>Slide.js</js:include>

  </tpl:template>

  <tpl:template>

    <tpl:apply mode="slideshow/contexts"/>

    <div js:class="sylma.slideshow.Container" js:parent-name="handler" js:name="slideshow" class="slideshow">

      <tpl:apply mode="slideshow/content"/>

    </div>

  </tpl:template>

  <tpl:template mode="slideshow/content">

    <js:option name="directory" cast="x">
      <tpl:apply mode="slideshow/files" required="x"/>
    </js:option>

    <js:option name="delay" cast="x">
      <tpl:apply mode="slideshow/delay"/>
    </js:option>

    <tpl:apply mode="top"/>

    <div class="loading" js:node="loading"/>
    <div class="container" js:node="container">
      <tpl:apply mode="slideshow/container"/>
    </div>

    <tpl:apply mode="slideshow/pager"/>

  </tpl:template>

  <tpl:template match="*" mode="slideshow/delay">12000</tpl:template>

  <tpl:template mode="slideshow/container">
    <tpl:apply mode="query"/>
    <tpl:apply select="*" mode="slideshow/tree"/>
  </tpl:template>

  <tpl:template match="*" mode="slideshow/pager">

    <div class="pager" js:node="pager">
      <tpl:apply mode="pager/previous"/>
      <div class="pages" js:node="pages"/>
      <tpl:apply mode="pager/next"/>
    </div>

  </tpl:template>

  <tpl:template match="*" mode="pager/previous">
    <a href="javascript:void(0)" class="previous">
      <js:event name="click">
        %object%.goPrevious('normal');
        %object%.resetLoop();
      </js:event>
      <tpl:apply mode="pager/previous/content"/>
    </a>
  </tpl:template>

  <tpl:template match="*" mode="pager/next">
      <a href="javascript:void(0)" class="next">
        <js:event name="click">
          %object%.goNext('normal');
          %object%.resetLoop();
        </js:event>
        <tpl:apply mode="pager/previous/content"/>
      </a>
  </tpl:template>

  <tpl:template match="*" mode="pager/previous/content">&lt;&lt;</tpl:template>
  <tpl:template match="*" mode="pager/next/content">&gt;&gt;</tpl:template>

  <tpl:template match="*" mode="slideshow/item">
    <div js:class="sylma.slideshow.Slide" class="slide">
      <js:option name="path">
        <tpl:read select="path"/>
      </js:option>
      <js:option name="id">
        <tpl:apply mode="slideshow/parent"/>
      </js:option>
    </div>

  </tpl:template>

  <tpl:template match="*" mode="slideshow/parent">
    <tpl:read select="parent()/parent()/id"/>
  </tpl:template>

</tpl:templates>
