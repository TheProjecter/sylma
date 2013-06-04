<?xml version="1.0" encoding="utf-8"?>
<crud:crud
  xmlns:crud="http://2013.sylma.org/view/crud"
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:sql="http://2013.sylma.org/storage/sql"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:ls="http://2013.sylma.org/parser/security"
>

  <view:template>

    <tpl:apply mode="title">
      <tpl:read tpl:name="title" select="static()/title()"/>
    </tpl:apply>

    <div>
      <a>
        <tpl:token name="href">
          <le:path/>/insert
        </tpl:token>
        Insert
      </a>
      <a ls:owner="root" ls:group="admin" ls:mode="700">
        <tpl:token name="href">
          <le:path>/sylma/storage/sql/alter</le:path>?path=<view:get-schema/>
        </tpl:token>
        Structure
      </a>
      <table js:class="sylma.ui.Base">
        <tpl:apply select="static()" mode="head/row"/>
        <crud:include path="list"/>
      </table>
    </div>

  </view:template>

  <view:template match="*" mode="head/row">
    <thead>
      <tr>
        <th></th>
        <tpl:apply use="list-cols" mode="head/cell"/>
      </tr>
    </thead>
  </view:template>

  <view:template match="*" mode="head/cell">
    <th>
      <a href="#" js:class="sylma.ui.Base">
        <js:option name="name"><tpl:apply select="alias()"/></js:option>
        <js:event name="click">
          %parent%.getObject('container').update({order : %object%.get('name')});
          return false;
        </js:event>
        <tpl:apply select="title()"/>
      </a>
    </th>
  </view:template>

  <!-- Internal list -->

  <view:template mode="internal">

    <tpl:apply mode="init"/>

    <tbody js:name="container" js:class="sylma.crud.List">

      <js:option name="path">
        <crud:path/>
      </js:option>
      <js:option name="send.order">
        <le:argument name="order"/>
      </js:option>

      <tpl:apply select="*" mode="row"/>

      <tr>
        <td colspan="99">
          <tpl:apply select="pager()"/>
        </td>
      </tr>

    </tbody>

  </view:template>

  <view:template mode="init">

    <tpl:apply mode="init-pager"/>

    <le:check-argument name="order" format="string">
      <le:default>
        <tpl:apply select="$$list-order"/>
      </le:default>
    </le:check-argument>

    <sql:order>
      <le:argument name="order"/>
    </sql:order>

  </view:template>


  <view:template match="*" mode="row">
    <tr js:class="sylma.ui.Base">
      <td>
        <a title="Editer">
          <tpl:token name="href">
            <le:path/>/update?id=<tpl:read select="id"/>
          </tpl:token>
          E
        </a>
      </td>
      <tpl:apply use="list-cols" mode="cell"/>
    </tr>
  </view:template>

  <view:template match="*" mode="cell">
    <td>
      <tpl:apply/>
    </td>
  </view:template>

  <view:template match="sql:foreign">
    <tpl:apply select="ref()"/>
  </view:template>

</crud:crud>
