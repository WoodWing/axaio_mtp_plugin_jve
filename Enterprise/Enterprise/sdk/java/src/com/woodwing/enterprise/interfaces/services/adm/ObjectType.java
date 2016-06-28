/**
 * ObjectType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class ObjectType implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected ObjectType(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _Article = "Article";
    public static final java.lang.String _ArticleTemplate = "ArticleTemplate";
    public static final java.lang.String _Layout = "Layout";
    public static final java.lang.String _LayoutTemplate = "LayoutTemplate";
    public static final java.lang.String _Image = "Image";
    public static final java.lang.String _Advert = "Advert";
    public static final java.lang.String _AdvertTemplate = "AdvertTemplate";
    public static final java.lang.String _Plan = "Plan";
    public static final java.lang.String _Audio = "Audio";
    public static final java.lang.String _Video = "Video";
    public static final java.lang.String _Library = "Library";
    public static final java.lang.String _Dossier = "Dossier";
    public static final java.lang.String _DossierTemplate = "DossierTemplate";
    public static final java.lang.String _LayoutModule = "LayoutModule";
    public static final java.lang.String _LayoutModuleTemplate = "LayoutModuleTemplate";
    public static final java.lang.String _Task = "Task";
    public static final java.lang.String _Hyperlink = "Hyperlink";
    public static final java.lang.String _Presentation = "Presentation";
    public static final java.lang.String _Archive = "Archive";
    public static final java.lang.String _Spreadsheet = "Spreadsheet";
    public static final java.lang.String _Other = "Other";
    public static final java.lang.String _PublishForm = "PublishForm";
    public static final java.lang.String _PublishFormTemplate = "PublishFormTemplate";
    public static final ObjectType Article = new ObjectType(_Article);
    public static final ObjectType ArticleTemplate = new ObjectType(_ArticleTemplate);
    public static final ObjectType Layout = new ObjectType(_Layout);
    public static final ObjectType LayoutTemplate = new ObjectType(_LayoutTemplate);
    public static final ObjectType Image = new ObjectType(_Image);
    public static final ObjectType Advert = new ObjectType(_Advert);
    public static final ObjectType AdvertTemplate = new ObjectType(_AdvertTemplate);
    public static final ObjectType Plan = new ObjectType(_Plan);
    public static final ObjectType Audio = new ObjectType(_Audio);
    public static final ObjectType Video = new ObjectType(_Video);
    public static final ObjectType Library = new ObjectType(_Library);
    public static final ObjectType Dossier = new ObjectType(_Dossier);
    public static final ObjectType DossierTemplate = new ObjectType(_DossierTemplate);
    public static final ObjectType LayoutModule = new ObjectType(_LayoutModule);
    public static final ObjectType LayoutModuleTemplate = new ObjectType(_LayoutModuleTemplate);
    public static final ObjectType Task = new ObjectType(_Task);
    public static final ObjectType Hyperlink = new ObjectType(_Hyperlink);
    public static final ObjectType Presentation = new ObjectType(_Presentation);
    public static final ObjectType Archive = new ObjectType(_Archive);
    public static final ObjectType Spreadsheet = new ObjectType(_Spreadsheet);
    public static final ObjectType Other = new ObjectType(_Other);
    public static final ObjectType PublishForm = new ObjectType(_PublishForm);
    public static final ObjectType PublishFormTemplate = new ObjectType(_PublishFormTemplate);
    public java.lang.String getValue() { return _value_;}
    public static ObjectType fromValue(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        ObjectType enumeration = (ObjectType)
            _table_.get(value);
        if (enumeration==null) throw new java.lang.IllegalArgumentException();
        return enumeration;
    }
    public static ObjectType fromString(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        return fromValue(value);
    }
    public boolean equals(java.lang.Object obj) {return (obj == this);}
    public int hashCode() { return toString().hashCode();}
    public java.lang.String toString() { return _value_;}
    public java.lang.Object readResolve() throws java.io.ObjectStreamException { return fromValue(_value_);}
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumSerializer(
            _javaType, _xmlType);
    }
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumDeserializer(
            _javaType, _xmlType);
    }
    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(ObjectType.class);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ObjectType"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
