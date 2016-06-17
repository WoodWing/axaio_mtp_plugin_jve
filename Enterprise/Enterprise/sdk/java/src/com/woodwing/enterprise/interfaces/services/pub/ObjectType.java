/**
 * ObjectType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class ObjectType implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected ObjectType(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _value1 = "";
    public static final java.lang.String _value2 = "Article";
    public static final java.lang.String _value3 = "ArticleTemplate";
    public static final java.lang.String _value4 = "Layout";
    public static final java.lang.String _value5 = "LayoutTemplate";
    public static final java.lang.String _value6 = "Image";
    public static final java.lang.String _value7 = "Advert";
    public static final java.lang.String _value8 = "AdvertTemplate";
    public static final java.lang.String _value9 = "Plan";
    public static final java.lang.String _value10 = "Audio";
    public static final java.lang.String _value11 = "Video";
    public static final java.lang.String _value12 = "Library";
    public static final java.lang.String _value13 = "Dossier";
    public static final java.lang.String _value14 = "DossierTemplate";
    public static final java.lang.String _value15 = "LayoutModule";
    public static final java.lang.String _value16 = "LayoutModuleTemplate";
    public static final java.lang.String _value17 = "Task";
    public static final java.lang.String _value18 = "Hyperlink";
    public static final java.lang.String _value19 = "Spreadsheet";
    public static final java.lang.String _value20 = "Other";
    public static final java.lang.String _value21 = "PublishForm";
    public static final java.lang.String _value22 = "PublishFormTemplate";
    public static final ObjectType value1 = new ObjectType(_value1);
    public static final ObjectType value2 = new ObjectType(_value2);
    public static final ObjectType value3 = new ObjectType(_value3);
    public static final ObjectType value4 = new ObjectType(_value4);
    public static final ObjectType value5 = new ObjectType(_value5);
    public static final ObjectType value6 = new ObjectType(_value6);
    public static final ObjectType value7 = new ObjectType(_value7);
    public static final ObjectType value8 = new ObjectType(_value8);
    public static final ObjectType value9 = new ObjectType(_value9);
    public static final ObjectType value10 = new ObjectType(_value10);
    public static final ObjectType value11 = new ObjectType(_value11);
    public static final ObjectType value12 = new ObjectType(_value12);
    public static final ObjectType value13 = new ObjectType(_value13);
    public static final ObjectType value14 = new ObjectType(_value14);
    public static final ObjectType value15 = new ObjectType(_value15);
    public static final ObjectType value16 = new ObjectType(_value16);
    public static final ObjectType value17 = new ObjectType(_value17);
    public static final ObjectType value18 = new ObjectType(_value18);
    public static final ObjectType value19 = new ObjectType(_value19);
    public static final ObjectType value20 = new ObjectType(_value20);
    public static final ObjectType value21 = new ObjectType(_value21);
    public static final ObjectType value22 = new ObjectType(_value22);
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
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "ObjectType"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
